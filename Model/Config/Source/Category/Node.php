<?php
namespace Aligent\CategorySelector\Model\Config\Source\Category;

class Node
{
    public $id;
    public $name;

    /**
     * @var Node[] $node
     */
    public $children = [];

    function __construct(
        int $id,
        string $name
    ) {
        $this->id = $id;
        $this->name = $name;
    }

    /**
     * @param int $id
     * @return $this|null
     */
    function findById(int $id) {
        if ($this->id === $id) {
            return $this;
        }
        foreach ($this->children as $child) {
            if ($found = $child->findById($id)) {
                return $found;
            }
        }
        return null;
    }
}
