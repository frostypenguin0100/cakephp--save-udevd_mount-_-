<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database\Type;

use Cake\Database\Type\TimeType;
use Cake\I18n\DateTime;
use Cake\I18n\I18n;
use Cake\TestSuite\TestCase;
use DateTime as NativeDateTime;
use DateTimeImmutable;

/**
 * Test for the Time type.
 */
class TimeTypeTest extends TestCase
{
    /**
     * @var \Cake\Database\Type\TimeType
     */
    protected $type;

    /**
     * @var \Cake\Database\Driver
     */
    protected $driver;

    /**
     * Setup
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->type = new TimeType();
        $this->driver = $this->getMockBuilder('Cake\Database\Driver')->getMock();
    }

    /**
     * Teardown
     */
    public function tearDown(): void
    {
        parent::tearDown();
        I18n::setLocale(I18n::getDefaultLocale());
    }

    /**
     * Test toPHP
     */
    public function testToPHP(): void
    {
        $this->assertNull($this->type->toPHP(null, $this->driver));

        $result = $this->type->toPHP('00:00:00', $this->driver);
        $this->assertSame('00', $result->format('s'));

        $result = $this->type->toPHP('00:00:15', $this->driver);
        $this->assertSame('15', $result->format('s'));

        $result = $this->type->toPHP('16:30:15', $this->driver);
        $this->assertInstanceOf(DateTime::class, $result);
        $this->assertSame('16', $result->format('H'));
        $this->assertSame('30', $result->format('i'));
        $this->assertSame('15', $result->format('s'));
    }

    /**
     * Test converting string times to PHP values.
     */
    public function testManyToPHP(): void
    {
        $values = [
            'a' => null,
            'b' => '01:30:13',
        ];
        $expected = [
            'a' => null,
            'b' => new DateTime('01:30:13'),
        ];
        $this->assertEquals(
            $expected,
            $this->type->manyToPHP($values, array_keys($values), $this->driver)
        );
    }

    /**
     * Test converting to database format
     */
    public function testToDatabase(): void
    {
        $value = '16:30:15';
        $result = $this->type->toDatabase($value, $this->driver);
        $this->assertSame($value, $result);

        $date = new DateTime('16:30:15');
        $result = $this->type->toDatabase($date, $this->driver);
        $this->assertSame('16:30:15', $result);

        $date = new DateTime('2013-08-12 15:16:18');
        $result = $this->type->toDatabase($date, $this->driver);
        $this->assertSame('15:16:18', $result);
    }

    /**
     * Data provider for marshal()
     *
     * @return array
     */
    public static function marshalProvider(): array
    {
        $date = new DateTime('@1392387900');

        return [
            // invalid types.
            [null, null],
            [false, null],
            [true, null],
            ['', null],
            ['derpy', null],
            ['16-nope!', null],
            ['2014-02-14 13:14:15', null],

            // valid string types
            ['1392387900', $date],
            [1392387900, $date],
            ['13:10:10', new DateTime('13:10:10')],
            ['14:15', new DateTime('14:15:00')],

            [new DateTime('13:10:10'), new DateTime('13:10:10')],
            [new NativeDateTime('13:10:10'), new NativeDateTime('13:10:10')],
            [new DateTimeImmutable('13:10:10'), new DateTimeImmutable('13:10:10')],

            // valid array types
            [
                ['year' => 2014, 'month' => 2, 'day' => 14, 'hour' => 13, 'minute' => 14, 'second' => 15],
                new DateTime('13:14:15'),
            ],
            [
                [
                    'year' => 2014, 'month' => 2, 'day' => 14,
                    'hour' => 1, 'minute' => 14, 'second' => 15,
                    'meridian' => 'am',
                ],
                new DateTime('01:14:15'),
            ],
            [
                [
                    'year' => 2014, 'month' => 2, 'day' => 14,
                    'hour' => 1, 'minute' => 14, 'second' => 15,
                    'meridian' => 'pm',
                ],
                new DateTime('13:14:15'),
            ],
            [
                [
                    'hour' => 1, 'minute' => 14, 'second' => 15,
                ],
                new DateTime('01:14:15'),
            ],
            [
                [
                    'hour' => 1, 'minute' => 14,
                ],
                new DateTime('01:14:00'),
            ],

            // Invalid array types
            [
                ['hour' => '', 'minute' => '', 'second' => ''],
                null,
            ],
            [
                ['hour' => '', 'minute' => '', 'meridian' => ''],
                null,
            ],
            [
                ['hour' => 'nope', 'minute' => 14, 'second' => 15],
                null,
            ],
            [
                [
                    'year' => '2014', 'month' => '02', 'day' => '14',
                    'hour' => 'nope', 'minute' => 'nope',
                ],
                null,
            ],
        ];
    }

    /**
     * test marshalling data.
     *
     * @dataProvider marshalProvider
     * @param mixed $value
     * @param mixed $expected
     */
    public function testMarshal($value, $expected): void
    {
        $result = $this->type->marshal($value);
        if (is_object($expected)) {
            $this->assertEquals($expected, $result);
        } else {
            $this->assertSame($expected, $result);
        }
    }

    /**
     * Tests marshalling times using the locale aware parser
     */
    public function testMarshalWithLocaleParsing(): void
    {
        $expected = new DateTime('23:23:00');
        $result = $this->type->useLocaleParser()->marshal('11:23pm');
        $this->assertSame($expected->format('H:i'), $result->format('H:i'));
        $this->assertNull($this->type->marshal('derp:23'));
    }

    /**
     * Tests marshalling times in denmark.
     */
    public function testMarshalWithLocaleParsingDanishLocale(): void
    {
        $original = setlocale(LC_COLLATE, '0');
        $updated = setlocale(LC_COLLATE, 'da_DK.utf8');
        setlocale(LC_COLLATE, $original);
        $this->skipIf($updated === false, 'Could not set locale to da_DK.utf8, skipping test.');

        I18n::setLocale('da_DK');
        $expected = new DateTime('03:20:00');
        $result = $this->type->useLocaleParser()->marshal('03.20');
        $this->assertSame($expected->format('H:i'), $result->format('H:i'));
    }
}
