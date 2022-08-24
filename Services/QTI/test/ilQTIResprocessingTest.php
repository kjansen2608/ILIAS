<?php

declare(strict_types=1);

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 ********************************************************************
 */

use PHPUnit\Framework\TestCase;

class ilQTIResprocessingTest extends TestCase
{
    public function testConstruct(): void
    {
        $this->assertInstanceOf(ilQTIResprocessing::class, new ilQTIResprocessing());
    }

    public function testSetGetComment(): void
    {
        $instance = new ilQTIResprocessing();
        $instance->setComment('Some input.');
        $this->assertEquals('Some input.', $instance->getComment());
    }

    public function testSetGetScoremodel(): void
    {
        $instance = new ilQTIResprocessing();
        $instance->setScoremodel('Some input.');
        $this->assertEquals('Some input.', $instance->getScoremodel());
    }
}
