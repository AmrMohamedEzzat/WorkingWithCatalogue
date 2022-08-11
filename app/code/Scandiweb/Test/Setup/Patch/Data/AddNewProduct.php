<?php

namespace Scandiweb\Test\Setup\Patch\Data;
/* importing required classes  */
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\App\State;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\Catalog\Api\CategoryLinkManagementInterface;

class AddNewProduct implements DataPatchInterface {
    /* initializing variables needed*/
    protected ProductInterfaceFactory $productInterfaceFactory;

    protected ProductRepositoryInterface $productRepository;

    protected State $appState;

	protected EavSetup $eavSetup;

	protected SourceItemInterfaceFactory $sourceItemFactory;

	protected SourceItemsSaveInterface $sourceItemsSaveInterface;

    protected CategoryLinkManagementInterface $categoryLink;

    protected array $sourceItems = [];

    public function __construct(
        ProductInterfaceFactory $productInterfaceFactory,
        ProductRepositoryInterface $productRepository,
        State $appState,
        EavSetup $eavSetup,
		SourceItemInterfaceFactory $sourceItemFactory,
        SourceItemsSaveInterface $sourceItemsSaveInterface,
        CategoryLinkManagementInterface $categoryLink
    )
    {
        $this->appState = $appState;
        $this->productInterfaceFactory = $productInterfaceFactory;
        $this->productRepository = $productRepository;
		$this->eavSetup = $eavSetup;
        $this->sourceItemFactory = $sourceItemFactory;
        $this->sourceItemsSaveInterface = $sourceItemsSaveInterface;
        $this->categoryLink = $categoryLink;
    }

    public function apply(): void
    {
        /* emulating the area on which the execute function will work to adminhtml area */
        $this->appState->emulateAreaCode('adminhtml', [$this, 'execute']);
    }

    public function execute(): void
    {
        /* Create the product and make sure its not already created */
        $product = $this->productInterfaceFactory->create();

        if ($product->getIdBySku('my-new-product')) {
            return;
        }

        $attributeSetId = $this->eavSetup->getAttributeSetId(Product::ENTITY, 'Default');

        /* setting the mandatory attributes */
		$product->setTypeId(Type::TYPE_SIMPLE)
        ->setName('my new product')
        ->setAttributeSetId($attributeSetId)
        ->setSku('my-new-product')
        ->setPrice(5)
        ->setVisibility(Visibility::VISIBILITY_BOTH)
        ->setStatus(Status::STATUS_ENABLED);
        /* saving the product */
        $product = $this->productRepository->save($product);
        /* creating inventory to the product and setting the quantitiy in it */
        $sourceItem = $this->sourceItemFactory->create();
        $sourceItem->setSourceCode('default');
        $sourceItem->setQuantity(10);
        $sourceItem->setSku($product->getSku());
        $sourceItem->setStatus(SourceItemInterface::STATUS_IN_STOCK);
        $this->sourceItems[] = $sourceItem;
    
        $this->sourceItemsSaveInterface->execute($this->sourceItems);
        /* linking the product to the default category */
        $this->categoryLink->assignProductToCategories($product->getSku(), [2]);
    }

    public static function getDependencies():array
        {
            return [];
        }

    public function getAliases():array
        {
            return [];
        }
}