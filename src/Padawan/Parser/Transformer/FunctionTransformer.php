<?php

namespace Padawan\Parser\Transformer;

use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Param;
use Padawan\Domain\Project\Node\FunctionData;
use Padawan\Domain\Project\Node\MethodParam;
use Padawan\Parser\CommentParser;
use Padawan\Parser\ParamParser;
use Padawan\Parser\ReturnTypeParser;
use Padawan\Parser\UseParser;

class FunctionTransformer
{
    public function __construct(
        CommentParser $commentParser,
        ParamParser $paramParser,
        ReturnTypeParser $returnTypeParser,
        UseParser $useParser
    ) {
        $this->commentParser = $commentParser;
        $this->paramParser = $paramParser;
        $this->returnTypeParser = $returnTypeParser;
        $this->useParser = $useParser;
    }
    public function tranform(Function_ $node)
    {
        $function = new FunctionData($node->name);
        $function->startLine = $node->getAttribute("startLine");
        $function->endLine = $node->getAttribute("endLine");
        $this->parseComments($function, $node->getAttribute("comments"));
        foreach ($node->params AS $child) {
            if ($child instanceof Param) {
                $function->addArgument($this->tranformArgument($child));
            }
        }
        if (!isset($function->return) && isset($node->returnType)) {
            $function->return = $this->tranformReturnType($node);
        }
        return $function;
    }
    protected function parseComments(FunctionData $function, $comments)
    {
        if (is_array($comments)) {
            /** @var Comment */
            $comment = $this->commentParser->parse(
                $comments[count($comments) - 1]->getText()
            );
            if ($comment->isInheritDoc()) {
                $function->doc = Comment::INHERIT_MARK;
            } else {
                $function->doc = $comment->getDoc();
                $function->return = $comment->getReturn();
                foreach ($comment->getVars() as $var) {
                    if ($var instanceof MethodParam) {
                        $function->addParam($var);
                    }
                }
            }
        }
    }
    protected function tranformArgument(Param $node)
    {
        return $this->paramParser->parse($node);
    }
    protected function tranformReturnType(Function_ $node)
    {
        return $this->returnTypeParser->parse($node);
    }

    private $paramParser;
    private $commentParser;
    private $useParser;
}
