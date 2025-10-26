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

        return $fields
            ->onlyFields()
            ->findByColumn($request->get('field'));
    }
}
