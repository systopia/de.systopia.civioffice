<?php
declare(strict_types = 1);

/*-------------------------------------------------------+
| SYSTOPIA CiviOffice Integration                        |
| Copyright (C) 2020 SYSTOPIA                            |
| Author: J. Franz (franz@systopia.de)                   |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+-------------------------------------------------------*/

use Civi\Civioffice\FilesystemUtil;
use CRM_Civioffice_ExtensionUtil as E;

class CRM_Civioffice_DocumentRendererType_LocalUnoconv extends CRM_Civioffice_DocumentRendererType {

  public const UNOCONV_BINARY_PATH_SETTINGS_KEY = 'unoconv_binary_path';

  public const UNOCONV_LOCK_FILE_PATH_SETTINGS_KEY = 'unoconv_lock_file_path';

  private const TEMP_DIR_SETTINGS_KEY = 'temp_dir';

  /**
   * The path to the unoconv binary.
   */
  private ?string $unoconvBinaryPath;

  /**
   * Path of file used for the lock.
   */
  private ?string $lockFilePath;

  /**
   * @var resource|null
   *   File resource handle used for the lock.
   */
  private $lockFileHandle = NULL;

  private string $tempDir;

  public function __construct(?string $uri = NULL, ?string $name = NULL, array $configuration = []) {
    parent::__construct(
      $uri ?? 'unoconv-local',
      $name ?? E::ts('Local Universal Office Converter (unoconv)'),
      $configuration
    );
    $this->unoconvBinaryPath = $configuration[self::UNOCONV_BINARY_PATH_SETTINGS_KEY] ?? NULL;
    $this->lockFilePath = $configuration[self::UNOCONV_LOCK_FILE_PATH_SETTINGS_KEY] ?? NULL;
    $this->tempDir = $configuration[self::TEMP_DIR_SETTINGS_KEY] ?? sys_get_temp_dir();
  }

  /**
   * Is this renderer currently available?
   * Tests if the binary is there and responding
   *
   * @return boolean
   *   Whether this renderer is ready for use.
   */
  public function isReady(): bool {
    try {
      if (NULL === $this->unoconvBinaryPath || '' === $this->unoconvBinaryPath) {
        // no unoconv binary or wrapper script provided
        Civi::log()->debug('CiviOffice: Path to unoconv binary / wrapper script is missing');
        return FALSE;
      }

      // run a probe command
      $probe_command = "$this->unoconvBinaryPath --version 2>&1";
      [$returnCode, $output] = $this->runCommand($probe_command);

      if (0 !== $returnCode && 255 !== $returnCode) {
        Civi::log()->debug("CiviOffice: Error code $returnCode received from unoconv. Output was: $output");
        return FALSE;
      }

      $found = preg_grep('/^unoconv (\d+)\.(\d+)/i', $output);
      if (empty($found)) {
        Civi::log()->debug('CiviOffice: unoconv version number not found');
        return FALSE;
      }
    }
    catch (Exception $e) {
      CRM_Core_Session::setStatus(
        E::ts('An error occurred. See the CiviCRM log for details.'),
        E::ts('CiviOffice Error'),
        'error'
      );
      Civi::log()->warning('CiviOffice: Unoconv generic exception in isReady() check: ' . $e->getMessage());
      return FALSE;
    }

    return TRUE;
  }

  public function getSupportedOutputMimeTypes(): array {
    return [
      CRM_Civioffice_MimeType::PDF,
    ];
  }

  public function getSupportedInputMimeTypes(): array {
    return [CRM_Civioffice_MimeType::DOCX];
  }

  public static function getSettingsFormTemplate(): string {
    return 'CRM/Civioffice/Form/DocumentRenderer/Settings/LocalUnoconv.tpl';
  }

  public function buildSettingsForm(CRM_Civioffice_Form_DocumentRenderer_Settings $form): void {
    $form->add(
      'text',
      'unoconv_binary_path',
      E::ts('Path to the <code>unoconv</code> executable'),
      ['class' => 'huge'],
      TRUE
    );

    $form->add(
      'text',
      'unoconv_lock_file_path',
      E::ts('Path to a lock file'),
      ['class' => 'huge'],
      FALSE
    );

    $form->add(
      'text',
      'temp_dir',
      E::ts('Temporary directory'),
      NULL,
      TRUE
    );
  }

  public function validateSettingsForm(CRM_Civioffice_Form_DocumentRenderer_Settings $form): void {
    $values = $form->exportValues();
    $unoconvLockFilePath = $values['unoconv_lock_file_path'];

    // There used to be a file_exists() check here for validating that the unoconv binary exists in the given path.
    // We can't however check eg. /usr/bin/unoconv on a site with open_basedir restrictions in place as this check
    // would always fail. There is a check running `unoconv --version` in the isReady() method which implicitly
    // covers the validation of the unconv binary being accessible.

    if (!empty($unoconvLockFilePath)) {
      if (!file_exists($unoconvLockFilePath)) {
        if (!touch($unoconvLockFilePath)) {
          $form->_errors['unoconv_lock_file_path'] = E::ts('Cannot create lock file');
        }
      }
      elseif (!is_file($unoconvLockFilePath)) {
        $form->_errors['unoconv_lock_file_path'] = E::ts('This is not a file');
      }
      elseif (!is_writable($unoconvLockFilePath)) {
        $form->_errors['unoconv_lock_file_path'] = E::ts(
          'Lock file cannot be written. Please run: "chmod 777 %1"',
          [1 => $unoconvLockFilePath]
        );
      }
    }

    /** @var string $tempDir */
    $tempDir = $values['temp_dir'] ?? '';
    if ('' !== $tempDir) {
      if (file_exists($tempDir)) {
        if (!is_dir($tempDir) || !is_writable($tempDir)) {
          $form->setElementError('temp_dir', E::ts('This is not a writeable directory.'));
        }
      }
      elseif (!mkdir($tempDir, 0700, TRUE)) {
        $form->setElementError('temp_dir', E::ts('Directory could not be created.'));
      }
    }
  }

  /**
   * @throws \Exception
   */
  public function postProcessSettingsForm(CRM_Civioffice_Form_DocumentRenderer_Settings $form): void {
    $values = $form->exportValues();

    $renderer = $form->getDocumentRenderer();
    $renderer->setConfigItem(self::UNOCONV_BINARY_PATH_SETTINGS_KEY, $values['unoconv_binary_path']);
    $renderer->setConfigItem(self::UNOCONV_LOCK_FILE_PATH_SETTINGS_KEY, $values['unoconv_lock_file_path']);
    $renderer->setConfigItem(self::TEMP_DIR_SETTINGS_KEY, $values['temp_dir']);
  }

  /**
   * @throws \Exception
   */
  public function render(string $inputFile, string $outputFile, string $mimeType): void {
    $format = CRM_Civioffice_MimeType::mapMimeTypeToFileExtension($mimeType);
    $command = "$this->unoconvBinaryPath -v -f $format -o $outputFile $inputFile 2>&1";
    [$returnCode, $output] = $this->runCommand($command);

    if (0 !== $returnCode && 255 !== $returnCode) {
      Civi::log()->error("CiviOffice: Exception: Return code 0 expected, but is $returnCode: $output");

      if (file_exists($outputFile) && filesize($outputFile) === 0) {
        Civi::log()->debug("CiviOffice: File is empty: $outputFile");
      }

      throw new RuntimeException("Unoconv: Return code 0 expected, but is $returnCode");
    }
  }

  /**
   * Get the URL to configure this component
   *
   * @return string
   *   URL
   */
  public function getConfigPageURL(): string {
    return CRM_Utils_System::url('civicrm/admin/civioffice/settings/localunoconv');
  }

  /**
   * Get the (localised) component description
   *
   * @return string
   *   name
   */
  public function getDescription(): string {
    return E::ts(<<<'EOD'
      This document renderer employs the <code>unoconv</code> script
      on your server to convert documents using LibreOffice.
      EOD
    );
  }

  /**
   * Run unoconv in the current configuration with the given command
   *
   * @param string $command
   *   the command to run
   * @param string|null $workDir
   *   an optional working directory. before running the command, the directory will
   *   be changed to the given directory.
   *
   * @return array{int, string}
   *   [return code, output lines]
   *
   * @throws \RuntimeException
   */
  private function runCommand(string $command, ?string $workDir = NULL): array {
    if (NULL === $this->unoconvBinaryPath) {
      throw new RuntimeException('Path to unoconv binary not set');
    }

    // make sure the unoconv path is in the environment
    //  see https://stackoverflow.com/a/43083964
    $ourPath = dirname($this->unoconvBinaryPath);
    $paths = explode(PATH_SEPARATOR, getenv('PATH'));
    if (!in_array($ourPath, $paths, TRUE)) {
      $paths[] = $ourPath;
      putenv('PATH=' . implode(PATH_SEPARATOR, $paths));
    }

    // use the lock if this is set up
    $this->lock();
    try {
      /*
       * unoconv creates the directories .cache and .config in the home
       * directory. For this we use a temporary home directory.
       */
      $homeDir = $this->tempDir . '/civioffice' . mt_rand(100000, mt_getrandmax());
      if (!mkdir($homeDir, 0700, TRUE)) {
        throw new \RuntimeException("Couldn't create temporary directory '$homeDir'");
      }
      try {
        if (NULL !== $workDir && '' !== $workDir) {
          exec("cd {$workDir}; HOME={$homeDir} {$command}", $execOutput, $execReturnCode);
        }
        else {
          exec("HOME={$homeDir} {$command}", $execOutput, $execReturnCode);
        }
      }
      finally {
        FilesystemUtil::removeRecursive($homeDir);
      }
    }
    catch (Exception $e) {
      Civi::log()->debug('CiviOffice: Got execution exception: ' . $e->getMessage());

      throw $e;
    }
    finally {
      $this->unlock();
    }

    return [$execReturnCode, implode("\n", $execOutput)];
  }

  /**
   * wait for the unoconv resource to become available
   *
   * @throws \RuntimeException
   */
  private function lock(): void {
    if (NULL !== $this->lockFilePath && '' !== $this->lockFilePath) {
      $lockFileHandle = fopen($this->lockFilePath, 'w+');
      if (FALSE === $lockFileHandle) {
        throw new RuntimeException(E::ts('Could not open unoconv lock file.'));
      }

      if (!flock($lockFileHandle, LOCK_EX)) {
        fclose($lockFileHandle);

        throw new RuntimeException(E::ts('CiviOffice: Could not acquire unoconv lock.'));
      }

      $this->lockFileHandle = $lockFileHandle;
    }
  }

  /**
   * wait for the unoconv resource to become available
   */
  private function unlock(): void {
    if (NULL !== $this->lockFileHandle) {
      if (!flock($this->lockFileHandle, LOCK_UN)) {
        Civi::log()->debug('CiviOffice: Could not release unoconv lock.');
      }
      fclose($this->lockFileHandle);
      $this->lockFileHandle = NULL;
    }
  }

  public static function supportedConfiguration(): array {
    return [
      self::UNOCONV_BINARY_PATH_SETTINGS_KEY,
      self::UNOCONV_LOCK_FILE_PATH_SETTINGS_KEY,
      self::TEMP_DIR_SETTINGS_KEY,
    ];
  }

  public static function defaultConfiguration(): array {
    return [
      self::UNOCONV_BINARY_PATH_SETTINGS_KEY => '/usr/bin/unoconv',
      self::UNOCONV_LOCK_FILE_PATH_SETTINGS_KEY => CRM_Civioffice_Configuration::getHomeFolder() . '/unoconv.lock',
      self::TEMP_DIR_SETTINGS_KEY => sys_get_temp_dir(),
    ];
  }

}
