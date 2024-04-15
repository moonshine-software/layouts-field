<?php

declare(strict_types=1);

namespace MoonShine\Layouts\Fields;

use Closure;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\View\View;
use MoonShine\ActionButtons\ActionButton;
use MoonShine\Components\Dropdown;
use MoonShine\Components\Link;
use MoonShine\Fields\Field;
use MoonShine\Fields\Hidden;
use MoonShine\Layouts\Casts\LayoutItem;
use MoonShine\Layouts\Collections\LayoutItemCollection;
use MoonShine\Layouts\Collections\LayoutCollection;
use MoonShine\Layouts\Casts\LayoutsCast;
use MoonShine\Layouts\Contracts\LayoutContract;
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

    public function addLayout(
        string $title,
        string $name,
        iterable $fields,
    ): self {
        $this->layouts[] = new Layout(
            $title,
            $name,
            $fields,
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
            'resourceUri' => moonshineRequest()->getResourceUri(),
            'pageUri' => moonshineRequest()->getPageUri(),
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

        if(!$values instanceof LayoutItemCollection) {
            $values = (new LayoutsCast())->get(
                $this->getData(),
                $this->column(),
                $values,
                []
            );
        }


        $filled = $values->map(function (LayoutItem $data) use ($layouts) {
            /** @var Layout $layout */
            $layout = clone $layouts
                ->findByName($data->getName())
                ?->when(
                    $this->disableSort,
                    fn(Layout $l) => $l->disableSort()
                )
                ?->when(
                    $this->isForcePreview(),
                    fn(Layout $l) => $l->forcePreview()
                )
                ?->setKey($data->getKey());

            $fields = $layout->fields()->fillCloned($data->getValues());

            $layout
                ->setFields($fields)
                ->fields()
                ->prepend(Hidden::make('_layout')->setValue($data->getName()))
                ->prepareReindex($this);

            return $layout->removeButton($this->getRemoveButton());
        })->filter();

        return LayoutCollection::make($filled);
    }

    public function getAddButton(): ?ActionButton
    {
        if($this->disableAdd) {
            return null;
        }

        if (is_null($this->addButton)) {
            $this->addButton = ActionButton::make('Add layout')
                ->secondary();
        }

        return $this->addButton;
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
            $this->dropdown = Dropdown::make();
        }

        return $this->dropdown
            ->toggler(fn () => $this->getAddButton())
            ->items($this->getLayoutButtons());
    }

    public function getRemoveButton(): ?ActionButton
    {
        if($this->disableRemove) {
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

                if(is_null($layout)) {
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
        $this->resolveCallback($data, function (Field $field, mixed $value) {
            $field->beforeApply($value);
        });

        return $data;
    }

    /**
     * @throws Throwable
     */
    protected function resolveAfterApply(mixed $data): mixed
    {
        $this->resolveCallback($data, function (Field $field, mixed $value) {
            $field->afterApply($value);
        });

        return $data;
    }

    /**
     * @throws Throwable
     */
    protected function resolveAfterDestroy(mixed $data): mixed
    {
        $this->resolveCallback($data, function (Field $field, mixed $value) {
            $field->afterDestroy($value);
        }, fill: true);

        return $data;
    }

    /**
     * @throws Throwable
     */
    protected function resolveCallback(mixed $data, Closure $callback, bool $fill = false): mixed
    {
        $requestValues = array_filter($this->requestValue() ?: []);

        foreach ($requestValues as $value) {
            $layout = $this->getLayouts()->findByName($value['_layout']);

            if(is_null($layout)) {
                continue;
            }

            $layout->fields()
                ->onlyFields()
                ->each(function (Field $field, $index) use ($data, $callback, $fill): void {
                    $field->appendRequestKeyPrefix(
                        "{$this->column()}.$index",
                        $this->requestKeyPrefix()
                    );

                    $field->when($fill, fn(Field $f) => $f->resolveFill($data));

                    $callback($field, $data);
                });
        }

        return $data;
    }
}
