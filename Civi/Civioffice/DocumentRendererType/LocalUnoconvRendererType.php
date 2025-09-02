<?php
/*
 * Copyright (C) 2020 SYSTOPIA GmbH
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

namespace Civi\Civioffice\DocumentRendererType;

use Assert\Assertion;
use Civi\Civioffice\DocumentRendererTypeInterface;
use Civi\Civioffice\FilesystemUtil;
use CRM_Civioffice_ExtensionUtil as E;

final class LocalUnoconvRendererType implements DocumentRendererTypeInterface {

  public const UNOCONV_BINARY_PATH_SETTINGS_KEY = 'unoconv_binary_path';

  public const UNOCONV_LOCK_FILE_PATH_SETTINGS_KEY = 'unoconv_lock_file_path';

  private const TEMP_DIR_SETTINGS_KEY = 'temp_dir';

  /**
   * The path to the unoconv binary.
   */
  private ?string $unoconvBinaryPath = NULL;

  /**
   * Path of file used for the lock.
   */
  private ?string $lockFilePath = NULL;

  /**
   * @var resource|null
   *   File resource handle used for the lock.
   */
  private $lockFileHandle = NULL;

  private string $tempDir;

  public function __construct() {
    // @phpstan-ignore assign.propertyType
    $this->tempDir = $this->getDefaultConfiguration()[self::TEMP_DIR_SETTINGS_KEY];
  }

  public static function getName(): string {
    return 'unoconv-local';
  }

  public static function getTitle(): string {
    return E::ts('Local Universal Office Converter (unoconv)');
  }

  public function getSettingsFormTemplate(): string {
    return 'CRM/Civioffice/Form/DocumentRenderer/Settings/LocalUnoconv.tpl';
  }

  public function buildSettingsForm(\CRM_Civioffice_Form_DocumentRenderer_Settings $form): void {
    $form->add(
      'text',
      'unoconv_binary_path',
      E::ts('Path to the <code>unoconv</code> executable'),
      NULL,
      TRUE
    );

    $form->add(
      'text',
      'unoconv_lock_file_path',
      E::ts('Path to a lock file')
    );

    $form->add(
      'text',
      'temp_dir',
      E::ts('Temporary directory'),
      NULL,
      TRUE
    );
  }

  // phpcs:disable Generic.Metrics.CyclomaticComplexity.TooHigh
  public function validateSettingsForm(\CRM_Civioffice_Form_DocumentRenderer_Settings $form): void {
  // phpcs:enable
    $values = $form->exportValues();

    // There used to be a file_exists() check here for validating that the unoconv binary exists in the given path.
    // We can't however check e.g. /usr/bin/unoconv on a site with open_basedir restrictions in place as this check
    // would always fail. There is a check running `unoconv --version` in the isReady() method which implicitly
    // covers the validation of the unoconv binary being accessible.

    $unoconvLockFilePath = $values['unoconv_lock_file_path'];
    if (NULL !== $unoconvLockFilePath && '' !== $unoconvLockFilePath) {
      if (!file_exists($unoconvLockFilePath)) {
        if (!touch($unoconvLockFilePath)) {
          $form->setElementError('unoconv_lock_file_path', E::ts('Cannot create lock file'));
        }
      }
      elseif (!is_file($unoconvLockFilePath)) {
        $form->setElementError('unoconv_lock_file_path', E::ts('This is not a file'));
      }
      elseif (!is_writable($unoconvLockFilePath)) {
        $form->setElementError('unoconv_lock_file_path', E::ts(
          'Lock file cannot be written. Please run: "chmod 777 %1"', [1 => $unoconvLockFilePath]
        ));
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

    $unoconvBinaryPath = $form->_submitValues['unoconv_binary_path'];
    if (NULL !== $unoconvBinaryPath && '' !== $unoconvBinaryPath && [] === $form->_errors) {
      if (!$this->isReady([
        self::UNOCONV_BINARY_PATH_SETTINGS_KEY => $unoconvBinaryPath,
        self::UNOCONV_LOCK_FILE_PATH_SETTINGS_KEY => $unoconvLockFilePath,
        self::TEMP_DIR_SETTINGS_KEY => $tempDir,
      ])) {
        $form->setElementError('unoconv_binary_path', E::ts(
          "unoconv couldn't be executed. The log file might contain more details."
        ));
      }
    }
  }

  public function postProcessSettingsForm(\CRM_Civioffice_Form_DocumentRenderer_Settings $form): array {
    $values = $form->exportValues();

    return [
      self::UNOCONV_BINARY_PATH_SETTINGS_KEY => $values['unoconv_binary_path'],
      self::UNOCONV_LOCK_FILE_PATH_SETTINGS_KEY => $values['unoconv_lock_file_path'],
      self::TEMP_DIR_SETTINGS_KEY => $values['temp_dir'],
    ];
  }

  public function getDefaultConfiguration(): array {
    return [
      self::UNOCONV_BINARY_PATH_SETTINGS_KEY => '/usr/bin/unoconv',
      self::UNOCONV_LOCK_FILE_PATH_SETTINGS_KEY => \CRM_Civioffice_Configuration::getHomeFolder() . '/unoconv.lock',
      self::TEMP_DIR_SETTINGS_KEY => sys_get_temp_dir(),
    ];
  }

  public function getSupportedConfigurationItems(): array {
    return [
      self::UNOCONV_BINARY_PATH_SETTINGS_KEY,
      self::UNOCONV_LOCK_FILE_PATH_SETTINGS_KEY,
      self::TEMP_DIR_SETTINGS_KEY,
    ];
  }

  public function getSupportedOutputMimeTypes(array $configuration): array {
    return [\CRM_Civioffice_MimeType::PDF];
  }

  public function getSupportedInputMimeTypes(array $configuration): array {
    return [\CRM_Civioffice_MimeType::DOCX];
  }

  /**
   * Is this renderer currently available?
   * Tests if the binary is there and responding
   *
   * @return bool
   *   Whether this renderer is ready for use.
   */
  public function isReady(array $configuration): bool {
    $this->activateConfiguration($configuration);

    try {
      if (NULL === $this->unoconvBinaryPath || '' === $this->unoconvBinaryPath) {
        // no unoconv binary or wrapper script provided
        \Civi::log()->debug('CiviOffice: Path to unoconv binary / wrapper script is missing');
        return FALSE;
      }

      // run a probe command
      $probe_command = "$this->unoconvBinaryPath --version 2>&1";
      [$returnCode, $output] = $this->runCommand($probe_command);

      if (0 !== $returnCode && 255 !== $returnCode) {
        \Civi::log()->debug("CiviOffice: Error code $returnCode received from unoconv. Output was: $output");
        return FALSE;
      }

      if (preg_match('/^unoconv (\d+)\.(\d+)/i', $output) !== 1) {
        \Civi::log()->debug('CiviOffice: unoconv version number not found');
        return FALSE;
      }
    }
    // @phpstan-ignore-next-line Exception not rethrown.
    catch (\Exception $e) {
      \CRM_Core_Session::setStatus(
        E::ts('An error occurred. See the CiviCRM log for details.'),
        E::ts('CiviOffice Error'),
        'error'
      );
      \Civi::log()->warning('CiviOffice: Unoconv generic exception in isReady() check: ' . $e->getMessage());
      return FALSE;
    }

    return TRUE;
  }

  /**
   * @throws \Exception
   */
  public function render(array $configuration, string $inputFile, string $outputFile, string $mimeType): void {
    $this->activateConfiguration($configuration);

    $format = \CRM_Civioffice_MimeType::mapMimeTypeToFileExtension($mimeType);
    $command = "$this->unoconvBinaryPath -v -f $format -o $outputFile $inputFile 2>&1";
    [$returnCode, $output] = $this->runCommand($command);

    if (0 !== $returnCode && 255 !== $returnCode) {
      \Civi::log()->error("CiviOffice: Exception: Return code 0 expected, but is $returnCode: $output");

      if (file_exists($outputFile) && filesize($outputFile) === 0) {
        \Civi::log()->debug("CiviOffice: File is empty: $outputFile");
      }

      throw new \RuntimeException("Unoconv: Return code 0 expected, but is $returnCode");
    }
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
      throw new \RuntimeException('Path to unoconv binary not set');
    }

    // make sure the unoconv path is in the environment
    //  see https://stackoverflow.com/a/43083964
    $ourPath = dirname($this->unoconvBinaryPath);
    /** @var string $pathEnv */
    $pathEnv = getenv('PATH');
    $paths = explode(PATH_SEPARATOR, $pathEnv);
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
    catch (\Exception $e) {
      \Civi::log()->debug('CiviOffice: Got execution exception: ' . $e->getMessage());

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
        throw new \RuntimeException(E::ts('Could not open unoconv lock file.'));
      }

      if (!flock($lockFileHandle, LOCK_EX)) {
        fclose($lockFileHandle);

        throw new \RuntimeException(E::ts('CiviOffice: Could not acquire unoconv lock.'));
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
        \Civi::log()->debug('CiviOffice: Could not release unoconv lock.');
      }
      fclose($this->lockFileHandle);
      $this->lockFileHandle = NULL;
    }
  }

  /**
   * @phpstan-param array<string, mixed> $configuration
   */
  private function activateConfiguration(array $configuration): void {
    Assertion::nullOrString($configuration[self::UNOCONV_BINARY_PATH_SETTINGS_KEY] ?? NULL);
    $this->unoconvBinaryPath = $configuration[self::UNOCONV_BINARY_PATH_SETTINGS_KEY] ?? NULL;
    Assertion::nullOrString($configuration[self::UNOCONV_LOCK_FILE_PATH_SETTINGS_KEY] ?? NULL);
    $this->lockFilePath = $configuration[self::UNOCONV_LOCK_FILE_PATH_SETTINGS_KEY] ?? NULL;
    if (isset($configuration[self::TEMP_DIR_SETTINGS_KEY])) {
      Assertion::string($configuration[self::TEMP_DIR_SETTINGS_KEY]);
      $this->tempDir = $configuration[self::TEMP_DIR_SETTINGS_KEY];
    }
  }

}
