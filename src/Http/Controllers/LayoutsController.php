<?php

declare(strict_types=1);

namespace MoonShine\Layouts\Http\Controllers;

use MoonShine\Enums\PageType;
use MoonShine\Enums\ToastType;
use MoonShine\Fields\Fields;
use MoonShine\Http\Controllers\MoonShineController;
use MoonShine\Http\Responses\MoonShineJsonResponse;
use MoonShine\Layouts\Casts\LayoutItem;
use MoonShine\Layouts\Collections\LayoutItemCollection;
use MoonShine\Layouts\Fields\Layout;
use MoonShine\Layouts\Fields\Layouts;
use MoonShine\MoonShineRequest;
use Throwable;

final class LayoutsController extends MoonShineController
{
    /**
     * @throws Throwable
     */
    public function store(MoonShineRequest $request): MoonShineJsonResponse
    {
        $field = $this->getField($request);

        if (is_null($field)) {
            return MoonShineJsonResponse::make()
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
            return MoonShineJsonResponse::make()
                ->toast('Layout not found', ToastType::ERROR);
        }

        $layoutCount = (int) $request
            ->collect('counts')
            ->get($layout->name(), 0);

        if ($layout->hasLimit() && $layout->limit() <= $layoutCount) {
            return MoonShineJsonResponse::make()
                ->toast("Limit count {$layout->limit()}", ToastType::ERROR);
        }

        return MoonShineJsonResponse::make()->html((string) $layout);
    }

    /**
     * @throws Throwable
     */
    private function getField(MoonShineRequest $request): ?Layouts
    {
        $page = $request->getPage();

        if (! $resource = $request->getResource()) {
            $fields = Fields::make(is_null($page->pageType()) ? $page->components() : $page->fields());
        } else {
            $fields = match ($page->pageType()) {
                PageType::INDEX => $resource->getIndexFields(),
                PageType::DETAIL => $resource->getDetailFields(),
                PageType::FORM => $resource->getFormFields(),
                default => Fields::make($resource->fields())
            };
        }

        return $fields
            ->onlyFields()
            ->findByColumn($request->get('field'));
    }
}
