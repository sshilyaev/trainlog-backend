<?php

declare(strict_types=1);

namespace App\Service\Api\Calculators;

use RuntimeException;

/**
 * Tiny safe expression evaluator for calculators.
 *
 * Supported:
 * - operators: + - * / ^ (power), parentheses
 * - functions: log10(x), exp(x), sqrt(x), min(a,b), max(a,b)
 * - variables: only from $allowedVariables
 *
 * Definition expressions must not contain any other identifiers/operators.
 */
final class SafeExpressionEvaluator
{
    /**
     * @param array<string, float|int> $variables
     * @param array<int, string> $allowedVariables
     */
    public function evaluate(string $expression, array $variables, array $allowedVariables): float
    {
        $tokens = $this->tokenize($expression);
        $parser = new ExpressionParser(
            tokens: $tokens,
            variables: $variables,
            allowedVariables: $allowedVariables,
        );

        $astValue = $parser->parseExpression();
        if ($parser->hasMoreTokens()) {
            throw new RuntimeException('Extra tokens in expression');
        }

        // Avoid -0.0 in some formatting cases.
        $result = (float) $astValue;
        return $result === 0.0 ? 0.0 : $result;
    }

    /**
     * @return list<array{type:string,value:float|string|null}>
     */
    private function tokenize(string $expression): array
    {
        $len = \strlen($expression);
        $i = 0;
        $tokens = [];

        while ($i < $len) {
            $ch = $expression[$i];

            if (\ctype_space($ch)) {
                $i++;
                continue;
            }

            // Number
            if (\ctype_digit($ch) || $ch === '.') {
                $start = $i;
                $hasDot = $ch === '.';
                $i++;
                while ($i < $len) {
                    $c = $expression[$i];
                    if (\ctype_digit($c)) {
                        $i++;
                        continue;
                    }
                    if ($c === '.') {
                        if ($hasDot) {
                            break;
                        }
                        $hasDot = true;
                        $i++;
                        continue;
                    }
                    break;
                }
                $raw = \substr($expression, $start, $i - $start);
                if ($raw === '.' || $raw === '') {
                    throw new RuntimeException('Invalid number');
                }
                $tokens[] = ['type' => 'number', 'value' => (float) $raw];
                continue;
            }

            // Identifier (variable or function name)
            if (\ctype_alpha($ch) || $ch === '_') {
                $start = $i;
                $i++;
                while ($i < $len) {
                    $c = $expression[$i];
                    if (\ctype_alnum($c) || $c === '_') {
                        $i++;
                        continue;
                    }
                    break;
                }
                $raw = \substr($expression, $start, $i - $start);
                $tokens[] = ['type' => 'identifier', 'value' => $raw];
                continue;
            }

            // Single-character tokens
            if (\in_array($ch, ['+', '-', '*', '/', '^', '(', ')', ','], true)) {
                $tokens[] = ['type' => 'symbol', 'value' => $ch];
                $i++;
                continue;
            }

            throw new RuntimeException(\sprintf('Invalid character in expression: %s', $ch));
        }

        return $tokens;
    }
}

/**
 * @internal
 */
final class ExpressionParser
{
    /**
     * @param list<array{type:string,value:float|string|null}> $tokens
     * @param array<string, float|int> $variables
     * @param array<int, string> $allowedVariables
     */
    public function __construct(
        private readonly array $tokens,
        private readonly array $variables,
        private readonly array $allowedVariables,
        private int $pos = 0,
    ) {
    }

    public function hasMoreTokens(): bool
    {
        return $this->pos < \count($this->tokens);
    }

    /**
     * expr -> term (('+'|'-') term)*
     */
    public function parseExpression(): float|int
    {
        $value = $this->parseTerm();
        while ($this->peekSymbol() === '+' || $this->peekSymbol() === '-') {
            $op = $this->nextSymbol();
            $rhs = $this->parseTerm();
            $value = $op === '+' ? $value + $rhs : $value - $rhs;
        }
        return $value;
    }

    /**
     * term -> power (('*'|'/') power)*
     */
    private function parseTerm(): float|int
    {
        $value = $this->parsePower();
        while ($this->peekSymbol() === '*' || $this->peekSymbol() === '/') {
            $op = $this->nextSymbol();
            $rhs = $this->parsePower();
            $value = $op === '*' ? $value * $rhs : $value / $rhs;
        }
        return $value;
    }

    /**
     * power -> unary ('^' power)?
     * Right-associative: a^b^c = a^(b^c)
     */
    private function parsePower(): float|int
    {
        $value = $this->parseUnary();
        if ($this->peekSymbol() === '^') {
            $this->nextSymbol(); // consume ^
            $rhs = $this->parsePower();
            return $value ** $rhs;
        }
        return $value;
    }

    /**
     * unary -> '-' unary | primary
     */
    private function parseUnary(): float|int
    {
        if ($this->peekSymbol() === '-') {
            $this->nextSymbol();
            return -$this->parseUnary();
        }

        return $this->parsePrimary();
    }

    /**
     * primary -> number | variable | funcCall | '(' expr ')'
     */
    private function parsePrimary(): float|int
    {
        $tok = $this->peek();
        if ($tok === null) {
            throw new RuntimeException('Unexpected end of expression');
        }

        if ($tok['type'] === 'number') {
            $this->pos++;
            return $tok['value'];
        }

        if ($tok['type'] === 'identifier') {
            $identifier = (string) $tok['value'];
            $this->pos++;

            if ($this->peekSymbol() === '(') {
                return $this->parseFunctionCall($identifier);
            }

            // Variable
            if (!\in_array($identifier, $this->allowedVariables, true)) {
                throw new RuntimeException(\sprintf('Unknown variable in expression: %s', $identifier));
            }
            if (!\array_key_exists($identifier, $this->variables)) {
                throw new RuntimeException(\sprintf('Missing variable in expression: %s', $identifier));
            }
            return $this->variables[$identifier];
        }

        if ($this->peekSymbol() === '(') {
            $this->nextSymbol(); // (
            $value = $this->parseExpression();
            if ($this->nextSymbol() !== ')') {
                throw new RuntimeException('Expected )');
            }
            return $value;
        }

        throw new RuntimeException('Invalid expression');
    }

    /**
     * funcCall -> name '(' argList ')'
     * argList -> expr (',' expr)*
     *
     * @return float|int
     */
    private function parseFunctionCall(string $name): float|int
    {
        $allowedFunctions = ['log10', 'exp', 'sqrt', 'min', 'max', 'pow'];
        if (!\in_array($name, $allowedFunctions, true)) {
            throw new RuntimeException(\sprintf('Function not allowed: %s', $name));
        }

        $this->nextSymbol(); // (
        $args = [];
        if ($this->peekSymbol() !== ')') {
            $args[] = $this->parseExpression();
            while ($this->peekSymbol() === ',') {
                $this->nextSymbol(); // ,
                $args[] = $this->parseExpression();
            }
        }
        $this->nextSymbol(); // )

        return match ($name) {
            'log10' => $this->expectArgCountAndEval($args, 1, fn (float $x) => \log10($x)),
            'exp' => $this->expectArgCountAndEval($args, 1, fn (float $x) => \exp($x)),
            'sqrt' => $this->expectArgCountAndEval($args, 1, fn (float $x) => \sqrt($x)),
            'min' => $this->expectArgCountAndEval($args, 2, fn (float $a, float $b) => \min($a, $b)),
            'max' => $this->expectArgCountAndEval($args, 2, fn (float $a, float $b) => \max($a, $b)),
            'pow' => $this->expectArgCountAndEval($args, 2, fn (float $a, float $b) => $a ** $b),
            default => throw new RuntimeException('Function not supported'),
        };
    }

    /**
     * @param list<float|int> $args
     */
    private function expectArgCountAndEval(array $args, int $count, callable $fn): float|int
    {
        if (\count($args) !== $count) {
            throw new RuntimeException(\sprintf('Invalid arg count for function: expected %d', $count));
        }
        return $fn(...\array_map(static fn ($v) => (float) $v, $args));
    }

    /**
     * @return array{type:string,value:float|string|null}|null
     */
    private function peek(): ?array
    {
        return $this->pos < \count($this->tokens) ? $this->tokens[$this->pos] : null;
    }

    private function peekSymbol(): ?string
    {
        $tok = $this->peek();
        if ($tok === null || $tok['type'] !== 'symbol') {
            return null;
        }
        return (string) $tok['value'];
    }

    private function nextSymbol(): string
    {
        $tok = $this->peek();
        if ($tok === null || $tok['type'] !== 'symbol') {
            throw new RuntimeException('Expected symbol');
        }
        $this->pos++;
        return (string) $tok['value'];
    }
}

