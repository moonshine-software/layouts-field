<?php

declare(strict_types=1);

namespace MoonShine\Layouts\Fields;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Traits\Conditionable;
use MoonShine\ActionButtons\ActionButton;
use MoonShine\Components\FieldsGroup;
use MoonShine\Components\FlexibleRender;
use MoonShine\Components\Icon;
use MoonShine\Decorations\Flex;
use MoonShine\Fields\Field;
use MoonShine\Fields\Fields;
use MoonShine\Fields\Position;
use MoonShine\Fields\Preview;
use MoonShine\Layouts\Contracts\LayoutContract;
use Throwable;

final class Layout implements LayoutContract
{
    use Conditionable;

    private ?ActionButton $removeButton = null;

    private int $key = 0;

    private bool $disableSort = false;

    private bool $isForcePreview = false;

    public function __construct(
        private string $title,
        private string $name,
        private iterable $fields,
        private ?int $limit = null,
        private ?iterable $headingAdditionalFields = null
    ) {
    }

    public function title(): string
    {
        return $this->title;
    }

    public function setKey(int $key): self
    {
        $this->key = $key;

        return $this;
    }

    public function key(): int
    {
        return $this->key;
    }

    public function setLimit(?int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    public function hasLimit(): bool
    {
        return ! is_null($this->limit);
    }

    public function limit(): ?int
    {
        return $this->limit;
    }

    public function name(): string
    {
        return str($this->name)
            ->squish()
            ->snake()
            ->value();
    }

    public function setFields(iterable $fields): self
    {
        $this->fields = $fields;

        return $this;
    }

    public function headingAdditionalFields(iterable $fields): self
    {
        $this->headingAdditionalFields = $fields;

        return $this;
    }

    public function disableSort(): self
    {
        $this->disableSort = true;

        return $this;
    }

    public function forcePreview(): self
    {
        $this->isForcePreview = true;

        return $this;
    }

    /**
     * @throws Throwable
     */
    public function headingFields(): Fields
    {
        return Fields::make([
            Flex::make(array_filter([
                ! $this->disableSort ? Preview::make(
                    formatted: static fn () => Icon::make('heroicons.outline.bars-4')
                )
                    ->withoutWrapper()
                    ->customAttributes(['class' => 'handle', 'style' => 'cursor: move']) : null,

                Position::make()
                    ->withoutWrapper()
                    ->iterableAttributes(),

                FlexibleRender::make($this->title()),

                $this->getHeadingAdditionalFields(),

                $this->getRemoveButton(),
            ]))
                ->customAttributes(['class' => 'w-full'])
                ->itemsAlign('center')
                ->justifyAlign('start'),
        ]);
    }

    /**
     * @throws Throwable
     */
    public function fields(): Fields
    {
        if (! $this->fields instanceof Fields) {
            $this->fields = Fields::make($this->fields);
        }

        if ($this->isForcePreview) {
            $this->fields->onlyFields()
                ->map(fn (Field $f) => $f->forcePreview());
        }


        return $this->fields;
    }

    /**
     * @throws Throwable
     */
    public function getHeadingAdditionalFields(): Fields
    {
        if (! $this->headingAdditionalFields instanceof Fields) {
            $this->headingAdditionalFields = Fields::make($this->headingAdditionalFields);
        }

        return $this->headingAdditionalFields;
    }

    public function removeButton(?ActionButton $button): self
    {
        $this->removeButton = $button;

        return $this;
    }

    public function getRemoveButton(): ?ActionButton
    {
        return $this->removeButton;
    }

    /**
     * @throws Throwable
     */
    public function render(): View
    {
        return view('moonshine-layouts-field::layout', [
            'key' => $this->key(),
            'heading' => FieldsGroup::make($this->headingFields()),
            'fields' => FieldsGroup::make($this->fields()),
        ]);
    }

    /**
     * @throws Throwable
     */
    public function toHtml(): string
    {
        return (string) $this->render();
    }

    /**
     * @throws Throwable
     */
    public function __toString(): string
    {
        return (string) $this->render();
    }
}
