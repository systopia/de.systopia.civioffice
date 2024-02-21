<?php
/*
 * Copyright (C) 2024 SYSTOPIA GmbH
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation in version 3.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types = 1);

namespace Civi\Civioffice\Api4\Action\Civioffice;

use Civi\Api4\Generic\Result;
use Civi\Civioffice\PHPUnit\Traits\CreateMockTrait;
use Civi\Civioffice\Render\Queue\RenderQueue;
use Civi\Civioffice\Render\Queue\RenderQueueBuilder;
use Civi\Civioffice\Render\Queue\RenderQueueBuilderFactory;
use Civi\Civioffice\Render\Queue\RenderQueueRunner;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Civi\Civioffice\Api4\Action\Civioffice\RenderWebAction
 */
final class RenderWebActionTest extends TestCase {

  use CreateMockTrait;

  /**
   * @var \Civi\Civioffice\Api4\Action\Civioffice\RenderWebAction
   */
  private RenderWebAction $action;

  /**
   * @var \Civi\Civioffice\Render\Queue\RenderQueueBuilder&\PHPUnit\Framework\MockObject\MockObject
   */
  private MockObject $queueBuilderMock;

  /**
   * @var \Civi\Civioffice\Render\Queue\RenderQueueRunner&\PHPUnit\Framework\MockObject\MockObject
   */
  private MockObject $queueRunnerMock;

  protected function setUp(): void {
    parent::setUp();
    $queueBuilderFactoryMock = $this->createMock(RenderQueueBuilderFactory::class);
    $this->queueRunnerMock = $this->createMock(RenderQueueRunner::class);

    $this->action = $this->createApi4ActionMock(RenderWebAction::class,
      $queueBuilderFactoryMock,
      $this->queueRunnerMock
    );

    $this->queueBuilderMock = $this->createMock(RenderQueueBuilder::class);
    $queueBuilderFactoryMock->method('createQueueBuilder')
      ->willReturn($this->queueBuilderMock);
  }

  public function testRun(): void {
    $this->action
      ->setCheckPermissions(FALSE)
      ->setEntityType('TestEntity')
      ->setEntityIds([1, 2, 3])
      ->setRendererUri('rendererUri')
      ->setDocumentUri('documentUri')
      ->setMimeType('application/pdf')
      ->setActivityTypeId(11)
      ->setLiveSnippets(['foo' => 'bar'])
      ->setBatchSize(22);

    $this->queueBuilderMock->expects(static::once())->method('setEntityType')
      ->with('TestEntity')->willReturnSelf();
    $this->queueBuilderMock->expects(static::once())->method('setEntityIds')
      ->with([1, 2, 3])->willReturnSelf();
    $this->queueBuilderMock->expects(static::once())->method('setRendererUri')
      ->with('rendererUri')->willReturnSelf();
    $this->queueBuilderMock->expects(static::once())->method('setDocumentUri')
      ->with('documentUri')->willReturnSelf();
    $this->queueBuilderMock->expects(static::once())->method('setMimeType')
      ->with('application/pdf')->willReturnSelf();
    $this->queueBuilderMock->expects(static::once())->method('setActivityTypeId')
      ->with(11)->willReturnSelf();
    $this->queueBuilderMock->expects(static::once())->method('setLiveSnippets')
      ->with(['foo' => 'bar'])->willReturnSelf();
    $this->queueBuilderMock->expects(static::once())->method('setBatchSize')
      ->with(22)->willReturnSelf();

    $queueMock = $this->createMock(RenderQueue::class);
    $this->queueBuilderMock->method('build')->willReturn($queueMock);

    $this->queueRunnerMock->expects(static::once())->method('runViaWebUrl')
      ->with($queueMock)
      ->willReturn('/runner/path');

    $result = new Result();
    $this->action->_run($result);
    static::assertSame(['redirect' => '/runner/path'], $result->getArrayCopy());
  }

}
