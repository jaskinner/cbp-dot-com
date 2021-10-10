<?php
 namespace MailPoetVendor\Doctrine\ORM\Query\AST\Functions; if (!defined('ABSPATH')) exit; use MailPoetVendor\Doctrine\ORM\Query\AST\AggregateExpression; use MailPoetVendor\Doctrine\ORM\Query\Parser; use MailPoetVendor\Doctrine\ORM\Query\SqlWalker; final class MaxFunction extends FunctionNode { private $aggregateExpression; public function getSql(SqlWalker $sqlWalker) : string { return $this->aggregateExpression->dispatch($sqlWalker); } public function parse(Parser $parser) : void { $this->aggregateExpression = $parser->AggregateExpression(); } } 