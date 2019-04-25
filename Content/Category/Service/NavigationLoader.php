<?php declare(strict_types=1);

namespace Shopware\Core\Content\Category\Service;

use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Category\Exception\CategoryNotFoundException;
use Shopware\Core\Content\Category\Tree\Tree;
use Shopware\Core\Content\Category\Tree\TreeItem;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class NavigationLoader
{
    /**
     * @var SalesChannelRepository
     */
    private $categoryRepository;

    /**
     * @var TreeItem
     */
    private $treeItem;

    public function __construct(SalesChannelRepository $repository)
    {
        $this->categoryRepository = $repository;
        $this->treeItem = new TreeItem(null, []);
    }

    /**
     * Returns the full category tree. The provided active id will be marked as selected
     */
    public function load(string $activeId, SalesChannelContext $context): ?Tree
    {
        /** @var CategoryEntity $active */
        $active = $this->loadActive($activeId, $context);
        $rootId = $this->getRootId($active, $activeId);

        /** @var CategoryCollection $categories */
        $categories = $this->categoryRepository->search(new Criteria(), $context)->getEntities();

        return $this->getTree($rootId, $categories, $active);
    }

    /**
     * Returns the category tree level for the provided category id.
     *
     * @throws InconsistentCriteriaIdsException
     */
    public function loadLevel(string $categoryId, SalesChannelContext $context): ?Tree
    {
        /** @var CategoryEntity $active */
        $active = $this->loadActive($categoryId, $context);

        $criteria = new Criteria();
        $criteria->addFilter(new MultiFilter(
            MultiFilter::CONNECTION_OR,
            [
                new EqualsFilter('category.id', $categoryId),
                new EqualsFilter('category.parentId', $categoryId),
            ]
        ));

        /** @var CategoryCollection $categories */
        $categories = $this->categoryRepository->search($criteria, $context)->getEntities();
        $parentId = $categories->get($categoryId)->getParentId();

        return $this->getTree($parentId, $categories, $active);
    }

    private function getTree(?string $parentId, CategoryCollection $categories, CategoryEntity $active): Tree
    {
        $tree = $this->buildTree($parentId, $categories->getElements());

        return new Tree($active, $tree);
    }

    /**
     * @throws CategoryNotFoundException
     * @throws InconsistentCriteriaIdsException
     */
    private function loadActive(string $activeId, SalesChannelContext $context): CategoryEntity
    {
        $criteria = new Criteria([$activeId]);

        $active = $this->categoryRepository->search($criteria, $context)->first();
        if (!$active) {
            throw new CategoryNotFoundException($activeId);
        }

        return $active;
    }

    private function getRootId(CategoryEntity $active, string $activeId): string
    {
        if ($active->getPath() !== null) {
            $path = array_filter(explode('|', $active->getPath()));

            return array_shift($path);
        }

        return $activeId;
    }

    /**
     * @param CategoryEntity[] $categories
     *
     * @return TreeItem[]
     */
    private function buildTree(?string $parentId, array $categories): array
    {
        $mapped = [];
        foreach ($categories as $key => $category) {
            if ($category->getParentId() !== $parentId) {
                continue;
            }

            unset($categories[$key]);

            $item = clone $this->treeItem;
            $item->setCategory($category);
            $item->setChildren(
                $this->buildTree($category->getId(), $categories)
            );

            $mapped[$category->getId()] = $item;
        }

        return $mapped;
    }
}
