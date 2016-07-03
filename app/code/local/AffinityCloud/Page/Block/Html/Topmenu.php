<?php
class AffinityCloud_Page_Block_Html_Topmenu extends Mage_Page_Block_Html_Topmenu {
	
	    /**
     * Recursively generates top menu html from data that is specified in $menuTree
     *
     * @param Varien_Data_Tree_Node $menuTree
     * @param string $childrenWrapClass
     * @return string
     */
    protected function _getHtml(Varien_Data_Tree_Node $menuTree, $childrenWrapClass)
    {
        $html = '';
        $children = $menuTree->getChildren();
        $parentLevel = $menuTree->getLevel();
        $childLevel = is_null($parentLevel) ? 0 : $parentLevel + 1;
        $counter = 1;
        $childrenCount = $children->count();
        $parentPositionClass = $menuTree->getPositionClass();
        $itemPositionClassPrefix = $parentPositionClass ? $parentPositionClass . '-' : 'nav-';
        foreach ($children as $child)
        {
            $child->setLevel($childLevel);
            $child->setIsFirst($counter == 1);
            $child->setIsLast($counter == $childrenCount);
            $child->setPositionClass($itemPositionClassPrefix . $counter);
            $outermostClassCode = '';
            $outermostClass = $menuTree->getOutermostClass();
            if ($childLevel == 0 && $outermostClass) {
                $outermostClassCode = $outermostClass;
                $child->setClass($outermostClass);
            }
        	if ( ! $child->hasChildren())
        	{
            	$html .= '<li class="menu-item dropdown' . $this->_getRenderedMenuItemAttributes($child) . '">';
            	$html .= '<a href="' . $child->getUrl() . '" class="' . $outermostClassCode . '"><span>' . $this->escapeHtml($child->getName()) . '</span></a>';
            }
            else if ($childLevel == 1 && $child->hasChildren())
            {
                $html .= '<li class="menu-item dropdown dropdown-submenu' . $this->_getRenderedMenuItemAttributes($child) . '">';
                $html .= '<a href="' . $child->getUrl() . '" class="dropdown-toggle ' . $outermostClassCode . '" data-toggle="dropdown"><span>' . $this->escapeHtml($child->getName()) . '</span></a>';
                $html .= '<ul class="dropdown-menu level' . $childLevel . '">';
                $html .= $this->_getHtml($child, $childrenWrapClass);
                $html .= '</ul>';
            }
            else
            {
            	$html .= '<li class="menu-item dropdown dropdown-submenu' . $this->_getRenderedMenuItemAttributes($child) . '">';
            	$html .= '<a href="' . $child->getUrl() . '" class="dropdown-toggle ' . $outermostClassCode . '" data-toggle="dropdown"><span>' . $this->escapeHtml($child->getName()) . '</span></a>';
                $html .= '<ul class="dropdown-menu level' . $childLevel . '">';
                $html .= $this->_getHtml($child, $childrenWrapClass);
                $html .= '</ul>';
            }
            $html .= '</li>';
            $counter++;
        }
        return $html;
    }
    /**
     * Generates string with all attributes that should be present in menu item element
     *
     * @param Varien_Data_Tree_Node $item
     * @return string
     */
    protected function _getRenderedMenuItemAttributes(Varien_Data_Tree_Node $item)
    {
        $html = '';
        $attributes = $this->_getMenuItemAttributes($item);
        foreach ($attributes as $attributeName => $attributeValue)
        {
            $html .= ' ' . str_replace('"', '\"', $attributeValue);
        }
        return $html;
    }
    /**
     * Returns array of menu item's attributes
     *
     * @param Varien_Data_Tree_Node $item
     * @return array
     */
    protected function _getMenuItemAttributes(Varien_Data_Tree_Node $item)
    {
        $menuItemClasses = $this->_getMenuItemClasses($item);
        $attributes = array(implode(' ', $menuItemClasses));
        return $attributes;
    }
}