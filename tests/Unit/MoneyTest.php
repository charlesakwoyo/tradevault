<?php

use App\Support\Money;

test('arithmetic is exact at 8 decimal places', function () {
    expect(Money::add('0.1', '0.2'))->toBe('0.30000000')
        ->and(Money::sub('1', '0.00000001'))->toBe('0.99999999')
        ->and(Money::mul('1.5', '3'))->toBe('4.50000000')
        ->and(Money::div('10', '3'))->toBe('3.33333333')
        ->and(Money::sum(['1.1', '2.2', '3.3']))->toBe('6.60000000');
});

test('comparisons and sign helpers', function () {
    expect(Money::compare('1.0', '1'))->toBe(0)
        ->and(Money::isNegative('-0.00000001'))->toBeTrue()
        ->and(Money::isZero('-0'))->toBeTrue()
        ->and(Money::negate('5'))->toBe('-5.00000000');
});

test('invalid amounts are rejected', function (string $amount) {
    Money::normalize($amount);
})->throws(InvalidArgumentException::class)->with(['abc', '1e5', '1,000', '', '--1']);

test('division by zero is rejected', function () {
    Money::div('1', '0');
})->throws(InvalidArgumentException::class);
