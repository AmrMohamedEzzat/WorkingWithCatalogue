<?php

namespace Scandiweb\Test\Setup\Patch\Data;

/* importing required classes  */

use \Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Validation\ValidationException;
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

class AddNewProduct implements DataPatchInterface
{
    /* initializing variables needed*/
    /**
     * @var ProductInterfaceFactory
     */
    protected ProductInterfaceFactory $productInterfaceFactory;
    /**
     * @var ProductRepositoryInterface
     */
    protected ProductRepositoryInterface $productRepository;
    /**
     * @var State
     */
    protected State $appState;
    /**
     * @var EavSetup
     */
    protected EavSetup $eavSetup;
    /**
     * @var SourceItemInterfaceFactory
     */
    protected SourceItemInterfaceFactory $sourceItemFactory;
    /**
     * @var SourceItemsSaveInterface
     */
    protected SourceItemsSaveInterface $sourceItemsSaveInterface;
    /**
     * @var CategoryLinkManagementInterface
     */
    protected CategoryLinkManagementInterface $categoryLink;
    /**
     * @var array
     */
    protected array $sourceItems = [];

    /**
     * @param ProductInterfaceFactory $productInterfaceFactory
     * @param ProductRepositoryInterface $productRepository
     * @param State $appState
     * @param EavSetup $eavSetup
     * @param SourceItemInterfaceFactory $sourceItemFactory
     * @param SourceItemsSaveInterface $sourceItemsSaveInterface
     * @param CategoryLinkManagementInterface $categoryLink
     */
    public function __construct(
        ProductInterfaceFactory         $productInterfaceFactory,
        ProductRepositoryInterface      $productRepository,
        State                           $appState,
        EavSetup                        $eavSetup,
        SourceItemInterfaceFactory      $sourceItemFactory,
        SourceItemsSaveInterface        $sourceItemsSaveInterface,
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

    /**
     * @return void
     * @throws \Exception
     */
    public function apply(): void
    {
        /* emulating the area on which the execute function will work to adminhtml area */
        $this->appState->emulateAreaCode('adminhtml', [$this, 'execute']);
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\StateException
     * @throws \Magento\Framework\Validation\ValidationException
     */
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

    /**
     * @return array|string[]
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @return array|string[]
     */
    public function getAliases(): array
    {
        return [];
    }
}
