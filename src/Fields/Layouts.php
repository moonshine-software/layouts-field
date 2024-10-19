<?php

declare(strict_types=1);

namespace MoonShine\Layouts\Fields;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use MoonShine\AssetManager\Js;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Contracts\Core\ResourceContract;
use MoonShine\Contracts\UI\ActionButtonContract;
use MoonShine\Contracts\UI\HasFieldsContract;
use MoonShine\Layouts\Casts\LayoutItem;
use MoonShine\Layouts\Casts\LayoutsCast;
use MoonShine\Layouts\Collections\LayoutCollection;
use MoonShine\Layouts\Collections\LayoutItemCollection;
use MoonShine\Layouts\Contracts\LayoutContract;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\Dropdown;
use MoonShine\UI\Components\Link;
use MoonShine\UI\Fields\Field;
use MoonShine\UI\Fields\Hidden;
use Throwable;

final class Layouts extends Field
{
    protected string $view = 'moonshine-layouts-field::layouts';

    private array $layouts = [];

    private ?ActionButtonContract $addButton = null;

    private ?Dropdown $dropdown = null;

    private ?ActionButtonContract $removeButton = null;

    private bool $disableRemove = false;

    private bool $disableAdd = false;

    private bool $disableSort = false;

    private ?ResourceContract $resource = null;

    private bool $isSearchable = false;

    private ?PageContract $page = null;

    public function getAssets(): array
    {
        return [
            Js::make('/vendor/moonshine-layouts-field/js/layouts.js'),
        ];
    }

    public function addLayout(
        string $title,
        string $name,
        iterable $fields,
        ?int $limit = null,
        ?iterable $headingAdditionalFields = null,
    ): self {
        $this->layouts[] = new Layout(
            $title,
            $name,
            $fields,
            $limit,
            $headingAdditionalFields
        );

        return $this;
    }

    public function addButton(ActionButtonContract $button): self
    {
        $this->addButton = $button;

        return $this;
    }

    public function getAddRoute(): string
    {
        return route('moonshine.layouts-field.store', [
            'resourceUri' => $this->resource ? $this->resource->getUriKey() : moonshineRequest()->getResourceUri(),
            'pageUri' => $this->page ? $this->page->getUriKey() : moonshineRequest()->getPageUri(),
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
                    ->icon('plus')
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
        $values = $this->toValue();

        if (! $values instanceof LayoutItemCollection) {
            $values = (new LayoutsCast())->get(
                $this->getData()->getOriginal(),
                $this->getColumn(),
                $values,
                []
            );
        }

        $filled = $values ? $values->map(function (LayoutItem $data) use ($layouts) {
            /** @var ?Layout $layout */
            $layout = $layouts->findByName($data->getName());

            if (is_null($layout)) {
                return null;
            }

            $layout = clone $layout->when(
                $this->disableSort,
                fn (Layout $l): Layout => $l->disableSort()
            )
                ->when(
                    $this->isPreviewMode(),
                    fn (Layout $l): Layout => $l->forcePreview()
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
                ->prepareReindexNames($this);

            $fields = $this->fillClonedRecursively(
                $layout->getHeadingAdditionalFields(),
                $data->getValues()
            );

            $layout
                ->headingAdditionalFields($fields);

            return $layout->removeButton($this->getRemoveButton());
        })->filter() : [];

        return LayoutCollection::make($filled);
    }

    private function fillClonedRecursively(Collection $collection, mixed $data): Collection
    {
        return $collection->map(function (mixed $item) use ($data) {
            if ($item instanceof HasFieldsContract) {
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

    public function getAddButton(): ?ActionButtonContract
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
        $this->isSearchable = value($condition) ?? true;

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
            ->toggler(fn (): ?ActionButtonContract => $this->getAddButton())
            ->items($this->getLayoutButtons());
    }

    public function getRemoveButton(): ?ActionButtonContract
    {
        if ($this->disableRemove) {
            return null;
        }

        if (is_null($this->removeButton)) {
            $this->removeButton = ActionButton::make('')
                ->icon('trash')
                ->style('margin-left: auto')
                ->error();
        }

        return $this->removeButton
            ->onClick(fn (): string => 'remove', 'stop');
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
            ->previewMode()
            ->render();
    }

    protected function resolveOnApply(): ?Closure
    {
        return function ($item) {
            $requestValues = array_filter($this->getRequestValue() ?: []);

            $data = collect($requestValues)->map(function (array $value, $index): array {
                $layout = $this->getLayouts()->findByName($value['_layout']);
                unset($value['_layout']);

                if (is_null($layout)) {
                    return [];
                }

                $applyValues = [];

                $layout->fields()->onlyFields()->each(
                    function (Field $field) use ($value, $index, &$applyValues): void {
                        $field->appendRequestKeyPrefix(
                            "{$this->getColumn()}.$index",
                            $this->getRequestKeyPrefix()
                        );

                        $apply = $field->apply(
                            fn ($data): mixed => data_set($data, $field->getColumn(), $value[$field->getColumn()] ?? ''),
                            $value
                        );

                        data_set(
                            $applyValues,
                            $field->getColumn(),
                            data_get($apply, $field->getColumn())
                        );
                    }
                );

                return [
                    'key' => $index,
                    'name' => $layout->name(),
                    'values' => $applyValues,
                ];
            })->filter();

            data_set($item, $this->getColumn(), $data);

            return $item;
        };
    }

    /**
     * @throws Throwable
     */
    protected function resolveBeforeApply(mixed $data): mixed
    {
        return $this->resolveCallback($data, function (Field $field, mixed $value): void {
            $field->beforeApply($value);
        });
    }

    /**
     * @throws Throwable
     */
    protected function resolveAfterApply(mixed $data): mixed
    {
        return $this->resolveCallback($data, function (Field $field, mixed $value): void {
            $field->afterApply($value);
        });
    }

    /**
     * @throws Throwable
     */
    protected function resolveAfterDestroy(mixed $data): mixed
    {
        return $this->resolveCallback($data, function (Field $field, mixed $value): void {
            $field->afterDestroy($value);
        }, fill: true);
    }

    /**
     * @throws Throwable
     */
    protected function resolveCallback(mixed $data, Closure $callback, bool $fill = false): mixed
    {
        $requestValues = array_filter($this->getRequestValue() ?: []);

        foreach ($requestValues as $index => $value) {
            $layout = $this->getLayouts()->findByName($value['_layout']);

            if (is_null($layout)) {
                continue;
            }

            $layout->fields()
                ->onlyFields()
                ->each(function (Field $field) use ($data, $index, $value, $callback, $fill): void {
                    $field->appendRequestKeyPrefix(
                        "{$this->getColumn()}.$index",
                        $this->getRequestKeyPrefix()
                    );

                    $field->when($fill, fn (Field $f): Field => $f->resolveFill($data));

                    $callback($field, $value);
                });
        }

        return $data;
    }

    protected function viewData(): array
    {
        return [
            'addRoute' => $this->getAddRoute(),
            'fields' => $this->getFilledLayouts(),
            'dropdown' => $this->getDropdown(),
        ];
    }
}
