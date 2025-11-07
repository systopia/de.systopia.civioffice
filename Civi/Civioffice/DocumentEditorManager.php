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

final class DocumentEditorManager {

  private bool $inFileExtensionDeduplicate = FALSE;

  private DocumentEditorTypeContainer $typeContainer;

  /**
   * @var array<int, string>|null
   *   Mapping of editor ID to editor name.
   */
  private ?array $editorNames = NULL;

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

  public function deleteEditor(DocumentEditor $editor): void {
    if (NULL === $this->editorNames) {
      $this->getEditorNames();
    }

    unset($this->editorNames[$editor->getId()]);
    \Civi::settings()->set('civioffice_editors', $this->editorNames);
    \Civi::settings()->revert('civioffice_editor_' . $editor->getId());
  }

  /**
   * @return list<DocumentEditor>
   *   Editors without configured file extensions are at the end.
   */
  public function getAllEditors(): array {
    $editors = [];
    $editorsWithoutExtensions = [];
    foreach ($this->getEditorIds() as $id) {
      $editor = $this->getEditor($id);
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
   * @return list<DocumentEditor>
   *   Editors without configured file extensions are at the end.
   */
  public function getAllActiveEditors(): array {
    return array_values(array_filter($this->getAllEditors(), fn ($editor) => $editor->isActive()));
  }

  public function getEditor(int $id): DocumentEditor {
    $name = $this->getEditorNames()[$id] ?? NULL;
    /** @phpstan-var array{
     *   active: bool,
     *   fileExtensions: list<string>,
     *   type: string,
     *   typeConfig: array<string, mixed>,
     * }|null $configuration */
    $configuration = \Civi::settings()->get('civioffice_editor_' . $id);
    if (NULL === $name || NULL === $configuration) {
      throw new \InvalidArgumentException(sprintf('Could not load editor configuration with ID %d', $id));
    }

    $type = $this->typeContainer->get($configuration['type']);

    return new DocumentEditor($id, $name, $configuration, $type);
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
   */
  public function saveNewEditor(
    string $name,
    bool $active,
    array $fileExtensions,
    DocumentEditorTypeInterface $type,
    array $typeConfig
  ): void {
    $editorIds = $this->getEditorIds();
    $id = (array_pop($editorIds) ?? 0) + 1;
    $this->persistEditor($id, $name, $active, $fileExtensions, $type::getName(), $typeConfig);
  }

  /**
   * @return list<int>
   */
  private function getEditorIds(): array {
    return array_keys($this->getEditorNames());
  }

  /**
   * @return array<int, string>
   *   Mapping of editor ID to editor name.
   */
  private function getEditorNames(): array {
    if (NULL === $this->editorNames) {
      /** @var array<int, string>|null $editorNames */
      $editorNames = \Civi::settings()->get('civioffice_editors');

      $this->editorNames = is_array($editorNames) ? $editorNames : [];
    }

    return $this->editorNames;
  }

  /**
   * @param list<string> $fileExtensions
   * @param array<string, mixed> $typeConfig
   */
  private function persistEditor(
    int $id,
    string $name,
    bool $active,
    array $fileExtensions,
    string $typeName,
    array $typeConfig
  ): void {
    $configuration = [
      'active' => $active,
      'fileExtensions' => $fileExtensions,
      'type' => $typeName,
      'typeConfig' => $typeConfig,
    ];

    \Civi::settings()->set('civioffice_editor_' . $id, $configuration);
    $this->editorNames[$id] = $name;
    \Civi::settings()->set('civioffice_editors', $this->editorNames);

    if ($this->inFileExtensionDeduplicate || [] === $fileExtensions) {
      return;
    }

    try {
      $this->inFileExtensionDeduplicate = TRUE;
      foreach ($this->getAllEditors() as $editor) {
        if ($id === $editor->getId()) {
          continue;
        }

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
