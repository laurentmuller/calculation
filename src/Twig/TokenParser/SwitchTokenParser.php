<?php

/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Twig\TokenParser;

use App\Twig\Node\CaseNode;
use App\Twig\Node\SwitchNode;
use Twig\Error\SyntaxError;
use Twig\Node\Expression\Binary\OrBinary;
use Twig\Node\Node;
use Twig\Node\Nodes;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;
use Twig\TokenStream;

/**
 * Class SwitchTokenParser that parses {% switch %} tags.
 *
 * Based on the rejected Twig pull request: https://github.com/fabpot/Twig/pull/185.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 3.0
 */
final class SwitchTokenParser extends AbstractTokenParser
{
    #[\Override]
    public function getTag(): string
    {
        return 'switch';
    }

    #[\Override]
    public function parse(Token $token): SwitchNode
    {
        $lineno = $token->getLine();
        $parser = $this->parser;
        $stream = $parser->getStream();
        $nodes = ['value' => $parser->parseExpression()];

        $stream->expect(Token::BLOCK_END_TYPE);
        while ($this->isEmptyText($stream)) {
            $stream->next();
        }
        $stream->expect(Token::BLOCK_START_TYPE);

        $cases = [];
        $end = false;
        while (!$end) {
            $next = $stream->next();
            switch ($next->getValue()) {
                case 'case':
                    $expression = $parser->parseExpression();
                    $values = $this->splitExpression($expression);
                    $stream->expect(Token::BLOCK_END_TYPE);
                    $body = $this->parser->subparse(fn (Token $token): bool => $this->isFork($token));
                    $cases[] = new CaseNode($values, $body);
                    break;

                case 'default':
                    $stream->expect(Token::BLOCK_END_TYPE);
                    $nodes['default'] = $this->parser->subparse(fn (Token $token): bool => $this->isEnd($token));
                    break;

                case 'endswitch':
                    $end = true;
                    break;

                default:
                    throw new SyntaxError(\sprintf('Unexpected end of template. Twig was looking for the following tags "case", "default", or "endswitch" to close the "switch" block started at line %d).', $lineno));
            }
        }
        $nodes['cases'] = new Nodes($cases);
        $stream->expect(Token::BLOCK_END_TYPE);

        return new SwitchNode($nodes, $lineno);
    }

    private function isEmptyText(TokenStream $stream): bool
    {
        return $stream->test(Token::TEXT_TYPE) && '' === \trim((string) $stream->getCurrent()->getValue());
    }

    private function isEnd(Token $token): bool
    {
        return $token->test(['endswitch']);
    }

    private function isFork(Token $token): bool
    {
        return $token->test(['case', 'default', 'endswitch']);
    }

    /**
     * @return Node[]
     */
    private function splitExpression(Node $expression): array
    {
        if (!$expression instanceof OrBinary) {
            return [$expression];
        }

        $left = $expression->getNode('left');
        $right = $expression->getNode('right');

        return \array_merge(
            $this->splitExpression($left),
            $this->splitExpression($right)
        );
    }
}
