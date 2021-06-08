<?php
namespace Aligent\CategorySelector\Model\Config\Source;

use Magento\Framework\Data\Collection;

class Category implements \Magento\Framework\Data\OptionSourceInterface
{
    const MAX_DEPTH = 3;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory
     */
    protected $categoryCollectionFactory;

    /**
     * @var Category\NodeFactory
     */
    protected $nodeFactory;

    /**
     * @var Category\Node
     */
    protected $categories;

    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        \Aligent\CategorySelector\Model\Config\Source\Category\NodeFactory $nodeFactory
    ) {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->nodeFactory = $nodeFactory;
    }

    public function getMaxDepth(): int
    {
        return static::MAX_DEPTH;
    }


    /**
     * @return \Magento\Catalog\Model\ResourceModel\Category\Collection
     */
    public function getCategoryCollection()
    {
        /**
         * @var \Magento\Catalog\Model\ResourceModel\Category\Collection $categoryCollection
         */
        $categoryCollection = $this->categoryCollectionFactory->create();
        $categoryCollection->setStoreId(\Magento\Store\Model\Store::DEFAULT_STORE_ID);
        $categoryCollection->addNameToResult();
        $categoryCollection->addOrder('level', Collection::SORT_ORDER_ASC);
        $categoryCollection->addOrder('parent_id', Collection::SORT_ORDER_ASC);
        $categoryCollection->addOrder('position', Collection::SORT_ORDER_ASC);
        return $categoryCollection;
    }

    /**
     * @param \Magento\Catalog\Model\ResourceModel\Category\Collection $categoryCollection
     */
    public function filterCategoryCollection($categoryCollection)
    {
        $categoryCollection->addFieldToFilter('level', ['lteq' => $this->getMaxDepth()]);
    }

    public function loadCategories()
    {
        $categoryCollection = $this->getCategoryCollection();
        $this->filterCategoryCollection($categoryCollection);

        $this->categories = $this->nodeFactory->create([
            'id' => 0,
            'name' => 'Base category',
        ]);

        $parents = [];
        $orphans = [];
        foreach ($categoryCollection as $category) {
            $parentId = (int) $category->getParentId();

            /**
             * @var Category\Node $node
             */
            $node = $this->nodeFactory->create([
                'id' => (int) $category->getEntityId(),
                'name' => $category->getName(),
            ]);
            $parent = null;
            if (isset($parents[$parentId])) {
                $parent = $parents[$parentId];
            } else {
                $parent = $this->categories->findById($parentId);
                if ($parent) {
                    $parents[$parentId] = $parent;
                }
            }
            if ($parent) {
                $parent->children[] = $node;
            } else {
                $orphans[$parentId][] = $node;
            }
        }

        // N.B. $orphans should be empty here
    }

    /**
     * @param array $options
     * @param Category\Node $category
     * @param int $indentLevel
     */
    public function addOptions(array &$options, Category\Node $category, int $indentLevel) {
        $label = $category->name;
        if ($indentLevel > 0) {
            $label = str_repeat('&nbsp;', $indentLevel * 4) . $label;
        }
        $options[] = ['value' => $category->id, 'label' => $label];
        foreach ($category->children as $child) {
            $this->addOptions($options, $child, $indentLevel + 1);
        }
    }

    public function toOptionArray()
    {
        if (empty($this->categories)) {
            $this->loadCategories();
        }

        $options = [];
        /**
         * @var Category\Node $category
         */
        foreach ($this->categories->children as $category) {
            $this->addOptions($options, $category, 0);
        }

        return $options;
    }
}
