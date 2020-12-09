<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Twig\TokenParser;

use App\Twig\Node\SwitchNode;
use Twig\Error\SyntaxError;
use Twig\Node\Node;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * Class SwitchTokenParser that parses {% switch %} tags.
 * Based on the rejected Twig pull request: https://github.com/fabpot/Twig/pull/185.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 3.0
 */
final class SwitchTokenParser extends AbstractTokenParser
{
    public function decideIfEnd(Token $token): bool
    {
        return $token->test(['endswitch']);
    }

    public function decideIfFork(Token $token): bool
    {
        return $token->test(['case', 'default', 'endswitch']);
    }

    /**
     * {@inheritdoc}
     */
    public function getTag(): string
    {
        return 'switch';
    }

    /**
     * {@inheritdoc}
     */
    public function parse(Token $token): Node
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();

        $nodes = [
            'value' => $this->parser->getExpressionParser()->parseExpression(),
        ];

        $stream->expect(Token::BLOCK_END_TYPE);

        // There can be some whitespace between the {% switch %} and first {% case %} tag.
        while (Token::TEXT_TYPE === $stream->getCurrent()->getType() && '' === \trim($stream->getCurrent()->getValue())) {
            $stream->next();
        }

        $stream->expect(Token::BLOCK_START_TYPE);

        $expressionParser = $this->parser->getExpressionParser();
        $cases = [];
        $end = false;

        while (!$end) {
            $next = $stream->next();

            switch ($next->getValue()) {
                case 'case':
                    $values = [];
                    while (true) {
                        $values[] = $expressionParser->parsePrimaryExpression();
                        // Multiple allowed values?
                        if ($stream->test(Token::OPERATOR_TYPE, 'or')) {
                            $stream->next();
                        } else {
                            break;
                        }
                    }
                    $stream->expect(Token::BLOCK_END_TYPE);
                    $body = $this->parser->subparse([$this, 'decideIfFork']);
                    $cases[] = new Node([
                        'values' => new Node($values),
                        'body' => $body,
                    ]);
                    break;

                case 'default':
                    $stream->expect(Token::BLOCK_END_TYPE);
                    $nodes['default'] = $this->parser->subparse([$this, 'decideIfEnd']);
                    break;

                case 'endswitch':
                    $end = true;
                    break;

                default:
                    throw new SyntaxError(\sprintf('Unexpected end of template. Twig was looking for the following tags "case", "default", or "endswitch" to close the "switch" block started at line %d)', $lineno), -1);
            }
        }

        $nodes['cases'] = new Node($cases);

        $stream->expect(Token::BLOCK_END_TYPE);

        return new SwitchNode($nodes, [], $lineno, $this->getTag());
    }
}
