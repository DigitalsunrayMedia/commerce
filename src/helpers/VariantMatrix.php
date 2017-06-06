<?php
namespace craft\commerce\helpers;

use craft\commerce\elements\Product;
use craft\commerce\elements\Variant;
use craft\helpers\Json;

/**
 * Class VariantMatrix
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2015, Pixel & Tonic, Inc.
 * @license   https://craftcommerce.com/license Craft Commerce License Agreement
 * @see       https://craftcommerce.com
 * @package   Commerce\Helpers
 * @since     1.0
 */
class VariantMatrix
{
    // Public Methods
    // =========================================================================

    /**
     * Returns the HTML for a given product’s variant matrix.
     *
     * @param Product $product The product model
     * @param string  $name    The input name (sans namespace). Default is 'variants'.
     *
     * @return string The variant matrix HTML
     */
    public static function getVariantMatrixHtml(Product $product, $name = 'variants')
    {
        /** @var \craft\web\View $viewService */
        $viewService = Craft::$app->getView();
        $id = $viewService->formatInputId($name);

        $html = $viewService->render('commerce/products/_variant_matrix', [
            'id' => $id,
            'name' => $name,
            'variants' => $product->getVariants()
        ]);

        // Namespace the name/ID for JS
        $namespacedName = $viewService->namespaceInputName($name);
        $namespacedId = $viewService->namespaceInputId($id);

        // Get the field HTML
        list($fieldBodyHtml, $fieldFootHtml) = self::_getVariantFieldHtml($product, $namespacedName);

        $viewService->includeJsResource('commerce/js/VariantMatrix.js');

        $viewService->includeJs('new Craft.Commerce.VariantMatrix('.
            '"'.$namespacedId.'", '.
            Json::encode($fieldBodyHtml).', '.
            Json::encode($fieldFootHtml).', '.
            '"'.$namespacedName.'"'.
            ');');

        $viewService->registerTranslations('commerce', [
                'Actions',
                'Add a variant',
                'Add variant above',
                'Are you sure you want to delete the selected variants?',
                'Collapse',
                'Default',
                'Disable',
                'Disabled',
                'Enable',
                'Expand',
                'Set as the default variant'
        ]);

        return $html;
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns info about each variant field type for a variant matrix.
     *
     * @param Product $product The product model
     * @param string  $name    The input name (sans namespace)
     *
     * @return array
     */
    private static function _getVariantFieldHtml($product, $name)
    {
        // Create a fake Variant model so the field types have a way to get at the owner element, if there is one
        $variant = new Variant();
        $variant->setProduct($product);

        $variantFields = $variant->getFieldLayout()->getFields();

        foreach ($variantFields as $fieldLayoutField) {
            $fieldType = $fieldLayoutField->getField()->getFieldType();

            if ($fieldType) {
                $fieldType->element = $variant;
                $fieldType->setIsFresh(true);
            }
        }

        $templatesService = Craft::$app->getView();
        $templatesService->startJsBuffer();

        $bodyHtml = $templatesService->render('commerce/products/_variant_matrix_fields', [
            'namespace' => $name.'[__VARIANT__]',
            'variant' => $variant
        ]);

        $footHtml = $templatesService->clearJsBuffer();

        // Reset $_isFresh's
        foreach ($variantFields as $fieldLayoutField) {
            $fieldType = $fieldLayoutField->getField()->getFieldType();

            if ($fieldType) {
                $fieldType->setIsFresh(null);
            }
        }

        return [$bodyHtml, $footHtml];
    }
}