<?php

namespace Paysera\PhpCsFixerConfig\Parser\Entity;

use PhpCsFixer\Tokenizer\Token;
use RuntimeException;

class ContextualToken extends Token implements ItemInterface
{
    /**
     * @var ContextualToken|null
     */
    private $previousContextualToken;
    private $nextContextualToken;

    public function __construct($token)
    {
        if (is_string($token) && trim($token, " \t\n\r\0\x0B") === '' && $token !== '') {
            $token = [T_WHITESPACE, $token];
        }

        parent::__construct($token);
    }

    public function setNextContextualToken(ContextualToken $contextualToken)
    {
        $this->nextContextualToken = $contextualToken;
        $contextualToken->previousContextualToken = $this;
    }

    public function replaceWith(ContextualToken $contextualToken)
    {
        if ($this->previousContextualToken === null) {
            throw new RuntimeException('Cannot replace first contextual token');
        }

        $this->previousToken()->setNextContextualToken($contextualToken);
        if ($this->nextContextualToken !== null) {
            $contextualToken->setNextContextualToken($this->nextContextualToken);
        }
    }

    public function insertBefore(ContextualToken $contextualToken)
    {
        if ($this->previousContextualToken === null) {
            throw new RuntimeException('Cannot insert before first contextual token');
        }

        $this->previousToken()->setNextContextualToken($contextualToken);
        $contextualToken->setNextContextualToken($this);
    }

    public function insertAfter(ContextualToken $contextualToken)
    {
        if ($this->nextContextualToken !== null) {
            $contextualToken->setNextContextualToken($this->nextContextualToken);
        }
        $this->setNextContextualToken($contextualToken);
    }

    /**
     * @return ContextualToken|null
     */
    public function getNextToken()
    {
        return $this->nextContextualToken;
    }

    public function nextToken(): ContextualToken
    {
        if ($this->nextContextualToken === null) {
            throw new \RuntimeException('No more tokens');
        }

        return $this->nextContextualToken;
    }

    public function previousToken(): ContextualToken
    {
        if ($this->previousContextualToken === null) {
            throw new \RuntimeException('No more tokens');
        }

        return $this->previousContextualToken;
    }

    public function previousNonWhitespaceToken(): ContextualToken
    {
        $previousToken = $this->previousToken();
        while ($previousToken->isWhitespace()) {
            $previousToken = $previousToken->previousToken();
        }
        return $previousToken;
    }

    public function lastToken(): ContextualToken
    {
        return $this;
    }

    public function firstToken(): ContextualToken
    {
        return $this;
    }

    public function getComplexItemLists(): array
    {
        return [];
    }

    public function isSplitIntoSeveralLines(): bool
    {
        return strpos($this->getContent(), "\n") !== false;
    }

    public function getLineIndent()
    {
        $codeBefore = '';
        $token = $this;
        while (($token = $token->previousContextualToken) !== null) {
            $codeBefore = $token->getContent() . $codeBefore;
            $newLinePosition = strrpos($codeBefore, "\n");
            if ($newLinePosition !== false) {
                $codeBefore = substr($codeBefore, $newLinePosition + 1);
                break;
            }
        }
        if (preg_match('/^([\s\t]*)/', $codeBefore, $matches) !== 1) {
            throw new \RuntimeException('Expected regexp to always match when searching for line indent');
        }
        return $matches[1];
    }

    public function equalsToItem(ItemInterface $item): bool
    {
        return $item instanceof $this && $this instanceof $item && $this->equals($item);
    }
}