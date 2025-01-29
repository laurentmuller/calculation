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
use Twig\Node\Node;
use Twig\Node\Nodes;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

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
    public function getTag(): string
    {
        return 'switch';
    }

    public function parse(Token $token): SwitchNode
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();
        $expressionParser = $this->parser->getExpressionParser();
        /** @psalm-var array<string, Node> $nodes */
        $nodes = ['value' => $expressionParser->parseExpression()];

        $stream->expect(Token::BLOCK_END_TYPE);
        while ($stream->test(Token::TEXT_TYPE) && '' === \trim((string) $stream->getCurrent()->getValue())) {
            $stream->next();
        }
        $stream->expect(Token::BLOCK_START_TYPE);

        $cases = [];
        $end = false;
        while (!$end) {
            $next = $stream->next();
            switch ($next->getValue()) {
                case 'case':
                    $values = [];
                    while (true) {
                        /** @psalm-var Node $node */
                        $node = $expressionParser->parsePrimaryExpression();
                        $values[] = $node;
                        // multiple allowed values?
                        if ($stream->test(Token::OPERATOR_TYPE, 'or')) {
                            $stream->next();
                        } else {
                            break;
                        }
                    }
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

        return new SwitchNode($nodes, [], $lineno);
    }

    private function isEnd(Token $token): bool
    {
        return $token->test(['endswitch']);
    }

    private function isFork(Token $token): bool
    {
        return $token->test(['case', 'default', 'endswitch']);
    }
}
