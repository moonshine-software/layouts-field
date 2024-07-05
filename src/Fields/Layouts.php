<?php

declare(strict_types=1);

namespace MoonShine\Layouts\Fields;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use MoonShine\ActionButtons\ActionButton;
use MoonShine\Components\Dropdown;
use MoonShine\Components\Link;
use MoonShine\Contracts\Fields\HasFields;
use MoonShine\Contracts\Resources\ResourceContract;
use MoonShine\Fields\Field;
use MoonShine\Fields\Hidden;
use MoonShine\Layouts\Casts\LayoutItem;
use MoonShine\Layouts\Casts\LayoutsCast;
use MoonShine\Layouts\Collections\LayoutCollection;
use MoonShine\Layouts\Collections\LayoutItemCollection;
use MoonShine\Layouts\Contracts\LayoutContract;
use MoonShine\Pages\Page;
use MoonShine\Support\Condition;
use Throwable;

final class Layouts extends Field
{
    protected string $view = 'moonshine-layouts-field::layouts';

    protected array $assets = [
        '/vendor/moonshine-layouts-field/js/layouts.js',
    ];

    private array $layouts = [];

    private ?ActionButton $addButton = null;

    private ?Dropdown $dropdown = null;

    private ?ActionButton $removeButton = null;

    private bool $disableRemove = false;

    private bool $disableAdd = false;

    private bool $disableSort = false;

    private ?ResourceContract $resource = null;

    private bool $isSearchable = false;

    private ?Page $page = null;

    public function nowOn(?Page $page = null, ?ResourceContract $resource = null): self
    {
        $this->page = $page;
        $this->resource = $resource;

        return $this;
    }

    public function addLayout(
        string $title,
        string $name,
        iterable $fields,
        ?int $limit = null
    ): self {
        $this->layouts[] = new Layout(
            $title,
            $name,
            $fields,
            $limit
        );

        return $this;
    }

    public function addButton(ActionButton $button): self
    {
        $this->addButton = $button;

        return $this;
    }

    public function getAddRoute(): string
    {
        return route('moonshine.layouts-field.store', [
            'resourceUri' => $this->resource ? $this->resource->uriKey() : moonshineRequest()->getResourceUri(),
            'pageUri' => $this->page ? $this->page->uriKey() : moonshineRequest()->getPageUri(),
        ]);
    }

    public function getLayouts(): LayoutCollection
    {
        return LayoutCollection::make($this->layouts);
    }

    public function getLayoutButtons(): array
    {
        return $this->getLayouts()
            ->map(
                fn (LayoutContract $layout) => Link::make('#', $layout->title())
                    ->icon('heroicons.outline.plus')
                    ->customAttributes(['@click.prevent' => "add(`{$layout->name()}`);closeDropdown()"])
            )
            ->toArray();
    }

    /**
     * @throws Throwable
     */
    public function getFilledLayouts(): LayoutCollection
    {
        $layouts = $this->getLayouts();
        /** @var LayoutItemCollection $value */
        $values = $this->toValue();

        if (! $values instanceof LayoutItemCollection) {
            $values = (new LayoutsCast())->get(
                $this->getData(),
                $this->column(),
                $values,
                []
            );
        }

        $filled = $values->map(function (LayoutItem $data) use ($layouts) {
            /** @var ?Layout $layout */
            $layout = $layouts->findByName($data->getName());

            if (is_null($layout)) {
                return null;
            }

            $layout = clone $layout->when(
                $this->disableSort,
                fn (Layout $l) => $l->disableSort()
            )
                ->when(
                    $this->isForcePreview(),
                    fn (Layout $l) => $l->forcePreview()
                )
                ->setKey($data->getKey());

            $fields = $this->fillClonedRecursively(
                $layout->fields(),
                $data->getValues()
            );

            $layout
                ->setFields($fields)
                ->fields()
                ->prepend(
                    Hidden::make('_layout')
                        ->customAttributes(['class' => '_layout-value'])
                        ->setValue($data->getName())
                )
                ->prepareAttributes()
                ->prepareReindex($this);

            return $layout->removeButton($this->getRemoveButton());
        })->filter();

        return LayoutCollection::make($filled);
    }

    private function fillClonedRecursively(Collection $collection, mixed $data): Collection
    {
        return $collection->map(function (mixed $item) use ($data) {
            if ($item instanceof HasFields) {
                $item = (clone $item)->fields(
                    $this->fillClonedRecursively($item->getFields(), $data)->toArray()
                );
            }

            if ($item instanceof Field) {
                $item->resolveFill($data);
            }

            return clone $item;
        });
    }

    public function getAddButton(): ?ActionButton
    {
        if ($this->disableAdd) {
            return null;
        }

        if (is_null($this->addButton)) {
            $this->addButton = ActionButton::make('Add layout')
                ->secondary();
        }

        return $this->addButton;
    }

    public function searchable(Closure|bool|null $condition = null): static
    {
        $this->isSearchable = Condition::boolean($condition, true);

        return $this;
    }


    public function disableAdd(): self
    {
        $this->disableAdd = true;

        return $this;
    }

    public function removeButton(ActionButton $button): self
    {
        $this->removeButton = $button;

        return $this;
    }

    public function disableRemove(): self
    {
        $this->disableRemove = true;

        return $this;
    }

    public function dropdown(Dropdown $dropdown): self
    {
        $this->dropdown = $dropdown;

        return $this;
    }

    public function getDropdown(): Dropdown
    {
        if (is_null($this->dropdown)) {
            $this->dropdown = Dropdown::make()->searchable($this->isSearchable);
        }

        return $this->dropdown
            ->toggler(fn () => $this->getAddButton())
            ->items($this->getLayoutButtons());
    }

    public function getRemoveButton(): ?ActionButton
    {
        if ($this->disableRemove) {
            return null;
        }

        if (is_null($this->removeButton)) {
            $this->removeButton = ActionButton::make('')
                ->icon('heroicons.outline.trash')
                ->customAttributes(['style' => 'margin-left: auto'])
                ->error();
        }

        return $this->removeButton
            ->onClick(fn () => 'remove', 'stop');
    }

    public function disableSort(): self
    {
        $this->disableSort = true;

        return $this;
    }

    protected function resolvePreview(): View|string
    {
        return $this
            ->disableRemove()
            ->disableAdd()
            ->disableSort()
            ->forcePreview()
            ->render();
    }

    protected function resolveOnApply(): ?Closure
    {
        return function ($item) {
            $requestValues = array_filter($this->requestValue() ?: []);

            $data = collect($requestValues)->map(function ($value, $index) {
                $layout = $this->getLayouts()->findByName($value['_layout']);
                unset($value['_layout']);

                if (is_null($layout)) {
                    return [];
                }

                $applyValues = [];

                $layout->fields()->onlyFields()->each(
                    function (Field $field) use ($value, $index, &$applyValues): void {
                        $field->appendRequestKeyPrefix(
                            "{$this->column()}.$index",
                            $this->requestKeyPrefix()
                        );

                        $apply = $field->apply(
                            fn ($data): mixed => data_set($data, $field->column(), $value[$field->column()] ?? ''),
                            $value
                        );

                        data_set(
                            $applyValues,
                            $field->column(),
                            data_get($apply, $field->column())
                        );
                    }
                );

                return [
                    'key' => $index,
                    'name' => $layout->name(),
                    'values' => $applyValues,
                ];
            })->filter();

            data_set($item, $this->column(), $data);

            return $item;
        };
    }

    /**
     * @throws Throwable
     */
    protected function resolveBeforeApply(mixed $data): mixed
    {
        return $this->resolveCallback($data, function (Field $field, mixed $value) {
            $field->beforeApply($value);
        });
    }

    /**
     * @throws Throwable
     */
    protected function resolveAfterApply(mixed $data): mixed
    {
        return $this->resolveCallback($data, function (Field $field, mixed $value) {
            $field->afterApply($value);
        });
    }

    /**
     * @throws Throwable
     */
    protected function resolveAfterDestroy(mixed $data): mixed
    {
        return $this->resolveCallback($data, function (Field $field, mixed $value) {
            $field->afterDestroy($value);
        }, fill: true);
    }

    /**
     * @throws Throwable
     */
    protected function resolveCallback(mixed $data, Closure $callback, bool $fill = false): mixed
    {
        $requestValues = array_filter($this->requestValue() ?: []);

        foreach ($requestValues as $index => $value) {
            $layout = $this->getLayouts()->findByName($value['_layout']);

            if (is_null($layout)) {
                continue;
            }

            $layout->fields()
                ->onlyFields()
                ->each(function (Field $field) use ($data, $index, $value, $callback, $fill): void {
                    $field->appendRequestKeyPrefix(
                        "{$this->column()}.$index",
                        $this->requestKeyPrefix()
                    );

                    $field->when($fill, fn (Field $f) => $f->resolveFill($data));

                    $callback($field, $value);
                });
        }

        return $data;
    }
}
