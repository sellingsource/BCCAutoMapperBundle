<?php

namespace BCC\AutoMapperBundle\Mapper;

use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Mapper maps objects and manages maps.
 *
 * @author Michel Salib <michelsalib@hotmail.com>
 */
class Mapper
{

    private $maps = array();

    /**
     * Creates and registers a default map given the source and destination types.
     * 
     * @param string $sourceType
     * @param string $destinationMap
     * @return DefaultMap 
     */
    public function createMap($sourceType, $destinationMap)
    {
        return $this->maps[$sourceType][$destinationMap] = new DefaultMap($sourceType, $destinationMap);
    }
    
    /**
     * Registers a map to the mapper.
     * 
     * @param MapInterface $map 
     */
    public function registerMap(MapInterface $map)
    {
        $this->maps[$map->getSourceType()][$map->getDestinationType()] = $map;
    }

    /**
     * Obtains a registered map for the given source and destination types.
     * 
     * @param string $sourceType
     * @param string $destinationType
     * @return MapInterface
     */
    public function getMap($sourceType, $destinationType)
    {
        if(!isset($this->maps[$sourceType])) {
            throw new \LogicException('There is no map that support this source type: '.$sourceType);
        }
        
        if(!isset($this->maps[$sourceType][$destinationType])) {
            throw new \LogicException('There is no map that support this destination type: '.$destinationType);
        }
        
        return $this->maps[$sourceType][$destinationType];
    }

    /**
     * Maps two object together, a map should exist.
     * 
     * @param mixed $source
     * @param mixed $destination
     * @return mixed The destination object
     */
    public function map($source, $destination)
    {
        $map = $this->getMap(
            \is_array($source) ? 'array' : \get_class($source),
            \is_array($destination) ? 'array' : \get_class($destination));
        $fieldAccessors = $map->getFieldAccessors();
        $fieldFilters = $map->getFieldFilters();
        
        foreach ($fieldAccessors as $path => $fieldAccessor) {
            $value = $fieldAccessor->getValue($source);
            
            if (isset($fieldFilters[$path])) {
                $value = $fieldFilters[$path]->filter($value);
            }

            $propertyAccessor = PropertyAccess::getPropertyAccessor();

            if ($map->getOverwriteIfSet())
            {
                $propertyAccessor->setValue($destination, $path, $value);
            }
            else
            {
                if ($propertyAccessor->getValue($destination, $path) == null)
                {
                    $propertyAccessor->setValue($destination, $path, $value);
                }
            }
        }
        
        return $destination;
    }

}
