<?php
 namespace MailPoetVendor\Doctrine\ORM\Internal\Hydration; if (!defined('ABSPATH')) exit; use MailPoetVendor\Doctrine\ORM\Mapping\ClassMetadata; use PDO; use function count; use function end; use function is_array; use function key; use function reset; class ArrayHydrator extends AbstractHydrator { private $_rootAliases = []; private $_isSimpleQuery = \false; private $_identifierMap = []; private $_resultPointers = []; private $_idTemplate = []; private $_resultCounter = 0; protected function prepare() { $this->_isSimpleQuery = count($this->_rsm->aliasMap) <= 1; foreach ($this->_rsm->aliasMap as $dqlAlias => $className) { $this->_identifierMap[$dqlAlias] = []; $this->_resultPointers[$dqlAlias] = []; $this->_idTemplate[$dqlAlias] = ''; } } protected function hydrateAllData() { $result = []; while ($data = $this->_stmt->fetch(PDO::FETCH_ASSOC)) { $this->hydrateRowData($data, $result); } return $result; } protected function hydrateRowData(array $row, array &$result) { $id = $this->_idTemplate; $nonemptyComponents = []; $rowData = $this->gatherRowData($row, $id, $nonemptyComponents); foreach ($rowData['data'] as $dqlAlias => $data) { $index = \false; if (isset($this->_rsm->parentAliasMap[$dqlAlias])) { $parent = $this->_rsm->parentAliasMap[$dqlAlias]; $path = $parent . '.' . $dqlAlias; if (!isset($nonemptyComponents[$parent])) { continue; } if ($this->_rsm->isMixed && isset($this->_rootAliases[$parent])) { $first = reset($this->_resultPointers); $baseElement =& $this->_resultPointers[$parent][key($first)]; } elseif (isset($this->_resultPointers[$parent])) { $baseElement =& $this->_resultPointers[$parent]; } else { unset($this->_resultPointers[$dqlAlias]); continue; } $relationAlias = $this->_rsm->relationMap[$dqlAlias]; $parentClass = $this->_metadataCache[$this->_rsm->aliasMap[$parent]]; $relation = $parentClass->associationMappings[$relationAlias]; if (!($relation['type'] & ClassMetadata::TO_ONE)) { $oneToOne = \false; if (!isset($baseElement[$relationAlias])) { $baseElement[$relationAlias] = []; } if (isset($nonemptyComponents[$dqlAlias])) { $indexExists = isset($this->_identifierMap[$path][$id[$parent]][$id[$dqlAlias]]); $index = $indexExists ? $this->_identifierMap[$path][$id[$parent]][$id[$dqlAlias]] : \false; $indexIsValid = $index !== \false ? isset($baseElement[$relationAlias][$index]) : \false; if (!$indexExists || !$indexIsValid) { $element = $data; if (isset($this->_rsm->indexByMap[$dqlAlias])) { $baseElement[$relationAlias][$row[$this->_rsm->indexByMap[$dqlAlias]]] = $element; } else { $baseElement[$relationAlias][] = $element; } end($baseElement[$relationAlias]); $this->_identifierMap[$path][$id[$parent]][$id[$dqlAlias]] = key($baseElement[$relationAlias]); } } } else { $oneToOne = \true; if (!isset($nonemptyComponents[$dqlAlias]) && !isset($baseElement[$relationAlias])) { $baseElement[$relationAlias] = null; } elseif (!isset($baseElement[$relationAlias])) { $baseElement[$relationAlias] = $data; } } $coll =& $baseElement[$relationAlias]; if (is_array($coll)) { $this->updateResultPointer($coll, $index, $dqlAlias, $oneToOne); } } else { $this->_rootAliases[$dqlAlias] = \true; $entityKey = $this->_rsm->entityMappings[$dqlAlias] ?: 0; if (!isset($nonemptyComponents[$dqlAlias])) { $result[] = $this->_rsm->isMixed ? [$entityKey => null] : null; $resultKey = $this->_resultCounter; ++$this->_resultCounter; continue; } if ($this->_isSimpleQuery || !isset($this->_identifierMap[$dqlAlias][$id[$dqlAlias]])) { $element = $this->_rsm->isMixed ? [$entityKey => $data] : $data; if (isset($this->_rsm->indexByMap[$dqlAlias])) { $resultKey = $row[$this->_rsm->indexByMap[$dqlAlias]]; $result[$resultKey] = $element; } else { $resultKey = $this->_resultCounter; $result[] = $element; ++$this->_resultCounter; } $this->_identifierMap[$dqlAlias][$id[$dqlAlias]] = $resultKey; } else { $index = $this->_identifierMap[$dqlAlias][$id[$dqlAlias]]; $resultKey = $index; } $this->updateResultPointer($result, $index, $dqlAlias, \false); } } if (!isset($resultKey)) { $this->_resultCounter++; } if (isset($rowData['scalars'])) { if (!isset($resultKey)) { $resultKey = isset($this->_rsm->indexByMap['scalars']) ? $row[$this->_rsm->indexByMap['scalars']] : $this->_resultCounter - 1; } foreach ($rowData['scalars'] as $name => $value) { $result[$resultKey][$name] = $value; } } if (isset($rowData['newObjects'])) { if (!isset($resultKey)) { $resultKey = $this->_resultCounter - 1; } $scalarCount = isset($rowData['scalars']) ? count($rowData['scalars']) : 0; foreach ($rowData['newObjects'] as $objIndex => $newObject) { $class = $newObject['class']; $args = $newObject['args']; $obj = $class->newInstanceArgs($args); if (count($args) === $scalarCount || $scalarCount === 0 && count($rowData['newObjects']) === 1) { $result[$resultKey] = $obj; continue; } $result[$resultKey][$objIndex] = $obj; } } } private function updateResultPointer(?array &$coll, $index, string $dqlAlias, bool $oneToOne) : void { if ($coll === null) { unset($this->_resultPointers[$dqlAlias]); return; } if ($oneToOne) { $this->_resultPointers[$dqlAlias] =& $coll; return; } if ($index !== \false) { $this->_resultPointers[$dqlAlias] =& $coll[$index]; return; } if (!$coll) { return; } end($coll); $this->_resultPointers[$dqlAlias] =& $coll[key($coll)]; return; } } 