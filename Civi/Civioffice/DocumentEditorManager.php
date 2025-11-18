<?php
/*
 * Copyright (C) 2025 SYSTOPIA GmbH
 *
 * This program is free software: you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License as published by the Free
 * Software Foundation, either version 3 of the License, or (at your option) any
 * later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types = 1);

namespace Civi\Civioffice;

use Civi\Api4\CiviofficeDocumentEditor;

/**
 * @phpstan-type whereT list<array{string, string|list<mixed>, 2?: mixed}>
 *   "list<mixed>" is actually a condition of a composite condition so we have
 *   a recursion that cannot be expressed in a phpstan type. The third entry is
 *   not given for composite conditions.
 */
final class DocumentEditorManager {

  private bool $inFileExtensionDeduplicate = FALSE;

  private DocumentEditorTypeContainer $typeContainer;

  /**
   * This method should only be used, if service injection isn't possible.
   */
  public static function getInstance(): self {
    // @phpstan-ignore return.type
    return \Civi::service(self::class);
  }

  public function __construct(DocumentEditorTypeContainer $typeContainer) {
    $this->typeContainer = $typeContainer;
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function deleteEditor(DocumentEditor $editor): void {
    CiviofficeDocumentEditor::delete(FALSE)
      ->addWhere('id', '=', $editor->getId())
      ->execute();
  }

  /**
   * @return list<DocumentEditor>
   *   Editors without configured file extensions are at the end.
   *
   * @throws \CRM_Core_Exception
   */
  public function getAllEditors(): array {
    return $this->getEditors();
  }

  /**
   * @return list<DocumentEditor>
   *   Editors without configured file extensions are at the end.
   *
   * @throws \CRM_Core_Exception
   */
  public function getAllActiveEditors(): array {
    return $this->getEditors([['is_active', '=', TRUE]]);
  }

  public function getEditor(int $id): DocumentEditor {
    $editor = $this->getEditors([['id', '=', $id]])[0] ?? NULL;

    if (NULL === $editor) {
      throw new \InvalidArgumentException(sprintf('Could not load editor configuration with ID %d', $id));
    }

    return $editor;
  }

  public function saveEditor(DocumentEditor $editor): void {
    $this->persistEditor(
      $editor->getId(),
      $editor->getName(),
      $editor->isActive(),
      $editor->getFileExtensions(),
      $editor->getTypeName(),
      $editor->getTypeConfig()
    );
  }

  /**
   * @param list<string> $fileExtensions
   * @param array<string, mixed> $typeConfig
   *
   * @throws \CRM_Core_Exception
   */
  public function saveNewEditor(
    string $name,
    bool $active,
    array $fileExtensions,
    DocumentEditorTypeInterface $type,
    array $typeConfig
  ): void {
    $this->persistEditor(NULL, $name, $active, $fileExtensions, $type::getName(), $typeConfig);
  }

  /**
   * @phpstan-param whereT $where
   *
   * @return list<DocumentEditor>
   *   Editors without configured file extensions are at the end.
   *
   * @throws \CRM_Core_Exception
   */
  private function getEditors(array $where = []): array {
    $editors = [];
    $editorsWithoutExtensions = [];

    $configurations = CiviofficeDocumentEditor::get(FALSE)->setWhere($where)->execute();
    /** @phpstan-var array{
     *   id: int,
     *   name: string,
     *   is_active: bool,
     *   file_extensions: list<string>,
     *   type: string,
     *   type_config: array<string, mixed>,
     * } $configuration */
    foreach ($configurations as $configuration) {
      $type = $this->typeContainer->get($configuration['type']);
      $editor = new DocumentEditor($configuration, $type);
      if ([] === $editor->getFileExtensions()) {
        $editorsWithoutExtensions[] = $editor;
      }
      else {
        $editors[] = $editor;
      }
    }

    return [...$editors, ...$editorsWithoutExtensions];
  }

  /**
   * @param list<string> $fileExtensions
   * @param array<string, mixed> $typeConfig
   *
   * @throws \CRM_Core_Exception
   */
  private function persistEditor(
    ?int $id,
    string $name,
    bool $active,
    array $fileExtensions,
    string $typeName,
    array $typeConfig
  ): void {
    $configuration = [
      'name' => $name,
      'is_active' => $active,
      'file_extensions' => $fileExtensions,
      'type' => $typeName,
      'type_config' => $typeConfig,
    ];

    if (NULL === $id) {
      $id = CiviofficeDocumentEditor::create(FALSE)
        ->setValues($configuration)
        ->execute()
        ->single()['id'];
    }
    else {
      CiviofficeDocumentEditor::update(FALSE)
        ->addWhere('id', '=', $id)
        ->setValues($configuration)
        ->execute();
    }

    if ($this->inFileExtensionDeduplicate || [] === $fileExtensions) {
      return;
    }

    try {
      $this->inFileExtensionDeduplicate = TRUE;
      foreach ($this->getEditors([['id', '!=', $id]]) as $editor) {
        $otherFileExtensions = array_diff($editor->getFileExtensions(), $fileExtensions);
        if ($editor->getFileExtensions() !== $otherFileExtensions) {
          $editor->setFileExtensions(array_values($otherFileExtensions));
          $this->saveEditor($editor);
        }
      }
    }
    finally {
      $this->inFileExtensionDeduplicate = FALSE;
    }
  }

}
