<?php

require_once 'abstract.php';

/**
 * Adlucent Feed Generator Shell Script
 *
 */
class Adlucent_Shell_Feed extends Mage_Shell_Abstract
{
    /**
     * Run script
     *
     */
    public function run()
    {
        try {
            set_time_limit(0);
            error_reporting(E_ALL);
            @Mage::app('admin')->setUseSessionInUrl(false);

            $products = Mage::getResourceModel('catalog/product_collection')
                ->addAttributeToSelect('sku')
                ->addAttributeToSelect('name')
                ->addAttributeToSelect('meta_description')
                ->addAttributeToSelect('url_path')
                ->addAttributeToSelect('url_key')
                ->addAttributeToSelect('image')
                ->joinField('stock_status',
                 'cataloginventory/stock_status',
                 'stock_status',
                 'product_id=entity_id',
                 '{{table}}.stock_id=1',
                 'left')
                ->joinField('qty',
                 'cataloginventory/stock_item',
                 'qty',
                 'product_id=entity_id',
                 '{{table}}.stock_id=1',
                 'left')
                ->addCategoryIds()
                ->addAttributeToSelect('price')
                ->addAttributeToSelect('special_price')
                ->addAttributeToSelect('special_date_from')
                ->addAttributeToSelect('age_group')
                ->addAttributeToSelect('color')
                ->addUrlRewrite()
                ->addFieldToFilter('status', array('eq' =>'1'))
                ->addFieldToFilter('visibility', array('neq' =>'1'))
            ;
            $fields = array(
                'SKU',
                'Title',
                'Description',
                'Product URL',
                'Category Hierarchy',
                'Image Link',
                'UPC',
                'Brand',
                'MPN',
                'Availability',
                'Inventory Level',
                'Advertised Price'
                'Shipping Price',
                'Age Group'
            );

            $age_groups = array(
                '216' => 'Kids',
                '217' => 'Adults'
            );

            // Output array
            $output_array = array();

            // Add header row
            $output_array[] = implode("\t", $fields);

            foreach ($products as $product)
            {
                if ($product->getVisibility() != 1) {
                    $row = array(
                        $product->getSku(),
                        $product->getName(),
                        preg_replace('/\s+/S', " ", $product->getMetaDescription()),
                        $this->getBaseURL() . $product->getUrlKey() . '.html',
                        'HOME / ' . $product->getName(), // Breadcrumb
                        $this->getBaseURL() . 'media/catalog/product' . $product->getImage(),
                        $product->getSku(), // UPC
                        'BRAND NAME', // Brand
                        $product->getSku(), // MPN Manf Part#
                        ($product->getStockStatus()) ? 'In Stock' : 'Out of Stock' , // Availability
                        $product->getQty(), // Inventory Level
                        $product->getPrice(),
                        '7.00', // Shipping cost
                        (!empty($age_groups[$product->getAgeGroup()])) ? $age_groups[$product->getAgeGroup()] : 'Adults'
                    );
                    $output_array[] =  implode("\t", $row);
                }
            }

            // Setup directory and file
            $varDir    = Mage::getBaseDir('var');
            $file_path = 'adlucent/feed.csv';
            $io        = new Varien_Io_File();
            $io->checkAndCreateFolder($varDir . DS . 'adlucent');

            // Output string
            $feed_data = implode("\r\n", $output_array);

            // Write the file
            $fh = fopen($varDir . DS . $file_path, 'w+') or die("can't open file");
            fwrite($fh, $feed_data);
            fclose($fh);
            chmod($varDir . DS . $file_path, 0644);

        } catch (Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }
    }
}

$shell = new Adlucent_Shell_Feed();
$shell->run();