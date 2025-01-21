<?php
/*
 * Copyright (C) 2025 SYSTOPIA GmbH
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

namespace Civi\Civioffice\EventSubscriber;

use Civi\API\Exception\UnauthorizedException;
use Civi\Civioffice\DocumentEditorManager;
use Civi\Civioffice\FileManagerInterface;
use Civi\Civioffice\Wopi\UserInfoService;
use Civi\Core\Event\GenericHookEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class CiviOfficeFilePageSubscriber implements EventSubscriberInterface {

  private DocumentEditorManager $editorManager;

  private FileManagerInterface $fileManager;

  private UserInfoService $userInfoService;

  /**
   * @inheritDoc
   */
  public static function getSubscribedEvents(): array {
    return ['hook_civicrm_pageRun' => 'onPageRun'];
  }

  public function __construct(
    DocumentEditorManager $editorManager,
    FileManagerInterface $fileManager,
    UserInfoService $userInfoService
  ) {
    $this->editorManager = $editorManager;
    $this->fileManager = $fileManager;
    $this->userInfoService = $userInfoService;
  }

  /**
   * @throws \Civi\API\Exception\UnauthorizedException
   * @throws \CRM_Core_Exception
   */
  public function onPageRun(GenericHookEvent $event): void {
    if (!$event->page instanceof \CRM_Core_Page_File) {
      return;
    }

    if (1 === \CRM_Utils_Request::retrieve('download', 'Integer')) {
      return;
    }

    /** @var int|null $fileId */
    $fileId = \CRM_Utils_Request::retrieve('id', 'Positive');
    if (NULL === $fileId) {
      return;
    }

    try {
      $this->userInfoService->getContactId();
    }
    catch (UnauthorizedException $e) {
      // No anonymous access to editor.
      return;
    }

    $file = $this->fileManager->get($fileId);
    if (NULL === $file) {
      return;
    }

    $mimeType = $file['mime_type'] ?? '';
    if ('' === $mimeType) {
      return;
    }

    $editors = $this->editorManager->getAllActiveEditors();
    foreach ($editors as $editor) {
      if ($editor->isFileSupported($file)) {
        $response = $editor->handleFile($file);
        $response->send();
        \CRM_Utils_System::civiExit();
      }
    }
  }

}
