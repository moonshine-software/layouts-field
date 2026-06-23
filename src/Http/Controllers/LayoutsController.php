<?php

declare(strict_types=1);

namespace MoonShine\Layouts\Http\Controllers;

use MoonShine\Contracts\Core\DependencyInjection\CrudRequestContract;
use MoonShine\Crud\JsonResponse;
use MoonShine\Laravel\Collections\Fields;
use MoonShine\Laravel\Http\Controllers\MoonShineController;
use MoonShine\Layouts\Casts\LayoutItem;
use MoonShine\Layouts\Collections\LayoutItemCollection;
use MoonShine\Layouts\Fields\Layout;
use MoonShine\Layouts\Fields\Layouts;
use MoonShine\Support\Enums\PageType;
use MoonShine\Support\Enums\ToastType;
use Throwable;

final class LayoutsController extends MoonShineController
{
    /**
     * @throws Throwable
     */
    public function store(CrudRequestContract $request): JsonResponse
    {
        $field = $this->getField($request);

        if (is_null($field)) {
            return JsonResponse::make()
                ->toast('Field not found', ToastType::ERROR);
        }

        /**
         * @var Layout $layout
         */
        $layout = $field
            ->setValue(LayoutItemCollection::make([
                new LayoutItem(
                    $request->get('name'),
                ),
            ]))
            ->getFilledLayouts()
            ->findByName($request->get('name'))
            ?->removeButton($field->getRemoveButton());

        if (is_null($layout)) {
            return JsonResponse::make()
                ->toast('Layout not found', ToastType::ERROR);
        }

        $layoutCount = (int) $request
            ->collect('counts')
            ->get($layout->name(), 0);

        if ($layout->hasLimit() && $layout->limit() <= $layoutCount) {
            return JsonResponse::make()
                ->toast("Limit count {$layout->limit()}", ToastType::ERROR);
        }

        return JsonResponse::make()->html((string) $layout);
    }

    /**
     * @throws Throwable
     */
    private function getField(CrudRequestContract $request): ?Layouts
    {
        $page = $request->getPage();

        if (! $resource = $request->getResource()) {
            $fields = Fields::make(is_null($page->getPageType()) ? $page->getComponents() : $page->getFields());
        } else {
            $fields = match ($page->getPageType()) {
                PageType::INDEX => $resource->getIndexFields(),
                PageType::DETAIL => $resource->getDetailFields(),
                PageType::FORM => $resource->getFormFields(),
                default => $page->getComponents(),
            };
        }

        $column = $request->get('field');

        // Original top-level search (for non-nested Layouts)
        $field = $fields->onlyFields()->findByColumn($column);

        if ($field instanceof Layouts) {
            return $field;
        }

        // Fallback: recursive search inside nested Layouts fields
        return $this->findNestedLayouts($fields->onlyFields(), $column);
    }

    /**
     * Recursively search for a Layouts field by column inside other Layouts fields.
     *
     * @param  iterable  $fields  Flattened fields collection (onlyFields result)
     * @param  string  $column  The column to find
     */
    private function findNestedLayouts(iterable $fields, string $column): ?Layouts
    {
        foreach ($fields as $field) {
            if (! $field instanceof Layouts) {
                continue;
            }

            // Direct match
            if ($field->getColumn() === $column) {
                return $field;
            }

            // Recurse into this Layouts field's Layout objects
            foreach ($field->getLayouts() as $layout) {
                $nested = $this->findNestedLayouts($layout->fields()->onlyFields(), $column);

                if ($nested !== null) {
                    // Set the parent prefix on the found field so that
                    // prepareReindexNames generates correct field names
                    // e.g. blocks[${index0}][content] instead of just content
                    $parentNameDot = $field->getNameDot();
                    $level = substr_count($parentNameDot, '$');

                    $nested->setNameAttribute(
                        $nested->generateNameFrom(
                            $parentNameDot,
                            "\${index$level}",
                            $nested->getColumn(),
                        )
                    );

                    return $nested;
                }
            }
        }

        return null;
    }
}
