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

namespace Civi\Civioffice\Wopi\Validation;

use Civi\Civioffice\Wopi\Util\DotNetTimeConverter;
use Civi\Civioffice\Wopi\WopiProofKey;
use PHPUnit\Framework\TestCase;

/**
 * Data is from https://github.com/nagi1/laravel-wopi/blob/77629b2a5dbaf759b01826f162c4678cd3c9b874/tests/Unit/Services/ProofValidatorTest.php
 *
 * phpcs:disable Generic.Files.LineLength.TooLong
 *
 * @covers \Civi\Civioffice\Wopi\Validation\WopiProofValidator
 */
final class WopiProofValidatorTest extends TestCase {
  private const PROOF_MODULUS = '0HOWUPFFgmSYHbLZZzdWO/HUOr8YNfx5NAl7GUytooHZ7B9QxQKTJpj0NIJ4XEskQW8e4dLzRrPbNOOJ+KpWHttXz8HoQXkkZV/gYNxaNHJ8/pRXGMZzfVM5vchhx/2C7ULPTrpBsSpmfWQ6ShaVoQzfThFUd0MsBvIN7HVtqzPx9jbSV04wAqyNjcro7F3iu9w7AEsMejHbFlWoN+J05dP5ixryF7+2U5RVmjMt7/dYUdCoiXvCMt2CaVr0XEG6udHU4iDKVKZjmUBc7cTWRzhqEL7lZ1yQfylp38Nd2xxVJ0sSU7OkC1bBDlePcYGaF3JjJgsmp/H5BNnlW9gSxQ==';

  private const PROOF_EXPONENT = 'AQAB';

  private const OLD_PROOF_MODULUS = 'u/ppb/da4jeKQ+XzKr69VJTqR7wgQp2jzDIaEPQVzfwod+pc1zvO7cwjNgfzF/KQGkltoOi9KdtMzR0qmX8C5wZI6wGpS8S4pTFAZPhXg5w4EpyR8fAagrnlOgaVLs0oX5UuBqKndCQyM7Vj5nFd+r53giS0ch7zDW0uB1G+ZWqTZ1TwbtV6dmlpVuJYeIPonOJgo2iuh455KuS2gvxZKOKR27Uq7W949oM8sqRjvfaVf4xDmyor++98XX0zadnf4pMWfPr3XE+bCXtB9jIPAxxMrALf5ncNRhnx0Wyf8zfM7Rfq+omp/HxCgusF5MC2/Ffnn7me/628zzioAMy5pQ==';

  private const OLD_PROOF_EXPONENT = 'AQAB';

  private WopiProofKey $oldProofKey;

  private WopiProofKey $proofKey;

  private WopiProofValidator $validator;

  protected function setUp(): void {
    parent::setUp();
    $this->validator = new WopiProofValidator();
    $this->proofKey = new WopiProofKey(self::PROOF_MODULUS, self::PROOF_EXPONENT);
    $this->oldProofKey = new WopiProofKey(self::OLD_PROOF_MODULUS, self::OLD_PROOF_EXPONENT);
  }

  public function testInvalid(): void {
    error_reporting(~E_USER_NOTICE);

    // Time is 2015-04-25 20:16:01.0 +00:00
    $this->setCurrentTicks(635655897610773532);
    static::assertFalse($this->isValid(new ProofValidatorInput(
    'test',
      '635655897610773532',
    'http://localhost',
    'some-key',
    'some-old-key'
    )));
    error_reporting(E_ALL);
  }

  public function testCurrentProof(): void {
    $wopiHeaderProofKey = 'IflL8OWCOCmws5qnDD5kYMraMGI3o+T+hojoDREbjZSkxbbx7XIS1Av85lohPKjyksocpeVwqEYm9nVWfnq05uhDNGp2MsNyhPO9unZ6w25Rjs1hDFM0dmvYx8wlQBNZ/CFPaz3inCMaaP4PtU85YepaDccAjNc1gikdy3kSMeG1XZuaDixHvMKzF/60DMfLMBIu5xP4Nt8i8Gi2oZs4REuxi6yxOv2vQJQ5+8Wu2Olm8qZvT4FEIQT9oZAXebn/CxyvyQv+RVpoU2gb4BreXAdfKthWF67GpJyhr+ibEVDoIIolUvviycyEtjsaEBpOf6Ne/OLRNu98un7WNDzMTQ==';

    $wopiOldHeaderProofKey = 'lWBTpWW8q80WC1eJEH5HMnGka4/LUF7zjUPqBwRMO0JzVcnjICvMP2TZPB2lJfy/4ctIstCN6P1t38NCTTbLWlXu+c4jqL9r2HPAdPPcPYiBAE1Evww93GpxVyOVcGADffshQvfaYFCfwL9vrBRstaQuWI0N5QlBCtWbnObF4dFsFWRRSZVU0X9YcNGhVX1NkVFVfCKG63Q/JkL+TnsJ7zqb7ZQpbS19tYyy4abtlGKWm3Zc1Jq9hPI3XVpoARXEO8cW6lT932QGdZiNr9aW2c15zTC6WiTxVeu7RW2Y0meX+Sfyrfu7GFb5JXDJAq8ZrUEUWABv1BOhHz5vLYHIA==';

    $accessToken = 'yZhdN1qgywcOQWhyEMVpB6NE3pvBksvcLXsrFKXNtBeDTPW%2fu62g2t%2fOCWSlb3jUGaz1zc%2fzOzbNgAredLdhQI1Q7sPPqUv2owO78olmN74DV%2fv52OZIkBG%2b8jqjwmUobcjXVIC1BG9g%2fynMN0itZklL2x27Z2imCF6xELcQUuGdkoXBj%2bI%2bTlKM';
    $wopiUrl = 'https://contoso.com/wopi/files/vHxYyRGM8VfmSGwGYDBMIQPzuE+sSC6kw+zWZw2Nyg?access_token=' . $accessToken;

    // Time is 2015-04-25 20:16:01.0 +00:00
    $this->setCurrentTicks(635655897610773532);
    static::assertTrue($this->isValid(new ProofValidatorInput(
      $accessToken,
      '635655897610773532',
      $wopiUrl,
      $wopiHeaderProofKey,
      $wopiOldHeaderProofKey
    )));
  }

  public function testOldProof(): void {
    $wopiHeaderProofKey = 'qQhMjQf9Zohj+S/wvhe+RD6W5TIEqJwDWO3zX9DB85yRe3Ide7EPQDCY9dAZtJpWkIDDzU+8FEwnexF0EhPimfCkmAyoPpkl2YYvQvvwUK2gdlk3WboWOVszm17p4dSDA0TDMPYsjaAGHKM/nPnTyIMzRyArEzoy2vNkLEP6qdBIuMP2aCtGsciwMjYifHYRIRenB7H7I+FkwH0UaoTUCoo2PJkyZjy1nK6OwGVWaWG0G8g7Zy+K3bRYV+7cNaM5SB720ezhmYYJJvsIdRvO7pLsjAuTo4KJhvmVFCipwyCdllVHY83GjuGOsAbHIIohl0Ttq59o0jp4w2wUs8U+mQ==';

    $wopiOldHeaderProofKey = 'PjKR1BTNNnfOrUzfo27cLIhlrbSiOVZaANadDyHxKij/77ZYId+liyXoawvvQQPgnBH1dW6jqpr6fh5ZxZ9IOtaV+cTSUGnGdRSn7FyKs1ClpApKsZBO/iRBLXw3HDWOLc0jnA2bnxY8yqbEPmH5IBC9taYzxnf7aGjc6AWFHfs6AEQ8lMio6UoASNzjy3VVNzUX+CK+e5Z45coT0X60mjaJmidGfPdWIfyUw8sSuUwxQa1uNXAd8IceRUL7j5s9/kk7EwsihCw1Y3L+XJGG5zMsGhM9bTK5mvxj30UdmZORouNHdywOfdHaB1iOeKOk+yvWFMW3JsYShWbUhZUOEQ==';

    $accessToken = 'zo7pjAbo%2fyof%2bvtUJn5gXpYcSl7TSSx0TbQGJbWJSll9PTjRRsAbG%2fSNL7FM2n5Ei3jQ8UJsW4RidT5R4tl1lrCi1%2bhGjxfWAC9pRRl6J3M1wZk9uFkWEeGzbtGByTkaGJkBqgKV%2ffxg%2bvATAhVr6E3LHCBAN91Wi8UG';

    $wopiUrl = 'https://contoso.com/wopi/files/RVQ29k8tf3h8cJ/Endy+aAMPy0iGhLatGNrhvKofPY9p2w?access_token=' . $accessToken;

    $this->setCurrentTicks(635655898374047766);
    static::assertTrue($this->isValid(new ProofValidatorInput(
      $accessToken,
      '635655898374047766',
      $wopiUrl,
      $wopiHeaderProofKey,
      $wopiOldHeaderProofKey
    )));
  }

  public function testOldProofKey(): void {
    $wopiHeaderProofKey = 'qF15pAAnOATqpUTLHIS/Z5K7OYFVjWcgKGbHPa0eHRayXsb6JKTelGQhvs74gEFgg1mIgcCORwAtMzLmEFmOHgrdvkGvRzT3jtVVtwkxEhQt8aQL20N0Nwn4wNah0HeBHskdvmA1G/qcaFp8uTgHpRYFoBaSHEP3AZVNFg5y2jyYR34nNj359gktc2ZyLel3J3j7XtyjpRPHvvYVQfh7RsArLQ0VGp8sL4/BDHdSsUyJ8FXe67TSrz6TMZPwhEUR8dYHYek9qbQjC+wxPpo3G/yusucm1gHo0BjW/l36cI8FRmNs1Fqaeppxqu31FhR8dEl7w5dwefa9wOUKcChF6A==';

    $wopiOldHeaderProofKey = 'KmYzHJ9tu4SXfoiWzOkUIc0Bh8H3eJrA3OnDSbu2hT68EuLTp2vmvvFcHyHIiO8DuKj7u13MxkpuUER6VSIJp3nYfm91uEE/3g61V3SzaeRXdnkcKUa5x+ulKViECL2n4mpHzNnymxojFW5Y4lKUU4qEGzjE71K1DSFTU/CBkdqycsuy/Oct8G4GhA3O4MynlCf64B9LIhlWe4G+hxZgxIO0pq7w/1SH27nvScWiljVqgOAKr0Oidk/7sEfyBcOlerLgS/A00nJYYJk23DjrKGTKz1YY0CMEsROJCMiW11caxr0aKseOYlfmb6K1RXxtmiDpJ2T4y8jintjEdzEWDA==';

    $accessToken = 'pbocsujrb9BafFujWh%2fuh7Y6S5nBnonddEzDzV0zEFrBwhiu5lzjXRezXDC9N4acvJeGVB5CWAcxPz6cJ6FzJmwA4ZgGP6FaV%2b6CDkJYID3FJhHFrbw8f2kRfaceRjV1PzXEvFXulnz2K%2fwwv0rF2B4A1wGQrnmwxGIv9cL5PBC4';

    $wopiUrl = 'https://contoso.com/wopi/files/DJNj59eQlM6BvwzAHkykiB1vNOWRuxT487+guv3v7HexfA?access_token=' . $accessToken;

    $this->setCurrentTicks(635655898062751632);
    static::assertTrue($this->isValid(new ProofValidatorInput(
      $accessToken,
      '635655898062751632',
      $wopiUrl,
      $wopiHeaderProofKey,
      $wopiOldHeaderProofKey
    )));
  }

  public function testCurrentProofKeyAfter20Minutes(): void {
    $wopiHeaderProofKey = 'IflL8OWCOCmws5qnDD5kYMraMGI3o+T+hojoDREbjZSkxbbx7XIS1Av85lohPKjyksocpeVwqEYm9nVWfnq05uhDNGp2MsNyhPO9unZ6w25Rjs1hDFM0dmvYx8wlQBNZ/CFPaz3inCMaaP4PtU85YepaDccAjNc1gikdy3kSMeG1XZuaDixHvMKzF/60DMfLMBIu5xP4Nt8i8Gi2oZs4REuxi6yxOv2vQJQ5+8Wu2Olm8qZvT4FEIQT9oZAXebn/CxyvyQv+RVpoU2gb4BreXAdfKthWF67GpJyhr+ibEVDoIIolUvviycyEtjsaEBpOf6Ne/OLRNu98un7WNDzMTQ==';

    $wopiOldHeaderProofKey = 'lWBTpWW8q80WC1eJEH5HMnGka4/LUF7zjUPqBwRMO0JzVcnjICvMP2TZPB2lJfy/4ctIstCN6P1t38NCTTbLWlXu+c4jqL9r2HPAdPPcPYiBAE1Evww93GpxVyOVcGADffshQvfaYFCfwL9vrBRstaQuWI0N5QlBCtWbnObF4dFsFWRRSZVU0X9YcNGhVX1NkVFVfCKG63Q/JkL+TnsJ7zqb7ZQpbS19tYyy4abtlGKWm3Zc1Jq9hPI3XVpoARXEO8cW6lT932QGdZiNr9aW2c15zTC6WiTxVeu7RW2Y0meX+Sfyrfu7GFb5JXDJAq8ZrUEUWABv1BOhHz5vLYHIA==';

    $accessToken = 'yZhdN1qgywcOQWhyEMVpB6NE3pvBksvcLXsrFKXNtBeDTPW%2fu62g2t%2fOCWSlb3jUGaz1zc%2fzOzbNgAredLdhQI1Q7sPPqUv2owO78olmN74DV%2fv52OZIkBG%2b8jqjwmUobcjXVIC1BG9g%2fynMN0itZklL2x27Z2imCF6xELcQUuGdkoXBj%2bI%2bTlKM';
    $wopiUrl = 'https://contoso.com/wopi/files/vHxYyRGM8VfmSGwGYDBMIQPzuE+sSC6kw+zWZw2Nyg?access_token=' . $accessToken;

    // Time is 2015-04-25 20:16:01.0 +00:00
    $this->setCurrentTicks(635655897610773532);
    \CRM_Utils_Time::setTime(date(DATE_ATOM, \CRM_Utils_Time::time() + 20 * 60 + 1));
    static::assertFalse($this->isValid(new ProofValidatorInput(
      $accessToken,
      '635655897610773532',
      $wopiUrl,
      $wopiHeaderProofKey,
      $wopiOldHeaderProofKey
    )));
  }

  public function testCurrentProofKeyBefore20Minutes(): void {
    $wopiHeaderProofKey = 'IflL8OWCOCmws5qnDD5kYMraMGI3o+T+hojoDREbjZSkxbbx7XIS1Av85lohPKjyksocpeVwqEYm9nVWfnq05uhDNGp2MsNyhPO9unZ6w25Rjs1hDFM0dmvYx8wlQBNZ/CFPaz3inCMaaP4PtU85YepaDccAjNc1gikdy3kSMeG1XZuaDixHvMKzF/60DMfLMBIu5xP4Nt8i8Gi2oZs4REuxi6yxOv2vQJQ5+8Wu2Olm8qZvT4FEIQT9oZAXebn/CxyvyQv+RVpoU2gb4BreXAdfKthWF67GpJyhr+ibEVDoIIolUvviycyEtjsaEBpOf6Ne/OLRNu98un7WNDzMTQ==';

    $wopiOldHeaderProofKey = 'lWBTpWW8q80WC1eJEH5HMnGka4/LUF7zjUPqBwRMO0JzVcnjICvMP2TZPB2lJfy/4ctIstCN6P1t38NCTTbLWlXu+c4jqL9r2HPAdPPcPYiBAE1Evww93GpxVyOVcGADffshQvfaYFCfwL9vrBRstaQuWI0N5QlBCtWbnObF4dFsFWRRSZVU0X9YcNGhVX1NkVFVfCKG63Q/JkL+TnsJ7zqb7ZQpbS19tYyy4abtlGKWm3Zc1Jq9hPI3XVpoARXEO8cW6lT932QGdZiNr9aW2c15zTC6WiTxVeu7RW2Y0meX+Sfyrfu7GFb5JXDJAq8ZrUEUWABv1BOhHz5vLYHIA==';

    $accessToken = 'yZhdN1qgywcOQWhyEMVpB6NE3pvBksvcLXsrFKXNtBeDTPW%2fu62g2t%2fOCWSlb3jUGaz1zc%2fzOzbNgAredLdhQI1Q7sPPqUv2owO78olmN74DV%2fv52OZIkBG%2b8jqjwmUobcjXVIC1BG9g%2fynMN0itZklL2x27Z2imCF6xELcQUuGdkoXBj%2bI%2bTlKM';
    $wopiUrl = 'https://contoso.com/wopi/files/vHxYyRGM8VfmSGwGYDBMIQPzuE+sSC6kw+zWZw2Nyg?access_token=' . $accessToken;

    // Time is 2015-04-25 20:16:01.0 +00:00
    $this->setCurrentTicks(635655897610773532);
    \CRM_Utils_Time::setTime(date(DATE_ATOM, \CRM_Utils_Time::time() - 20 * 60 - 1));
    static::assertFalse($this->isValid(new ProofValidatorInput(
      $accessToken,
      '635655897610773532',
      $wopiUrl,
      $wopiHeaderProofKey,
      $wopiOldHeaderProofKey
    )));
  }

  private function isValid(ProofValidatorInput $proofValidatorInput): bool {
    return $this->validator->isValid($proofValidatorInput, $this->proofKey, $this->oldProofKey);
  }

  private function setCurrentTicks(int $ticks): void {
    putenv('TIME_FUNC=frozen');
    \CRM_Utils_Time::setTime(DotNetTimeConverter::toDateTime($ticks)->format(DATE_ATOM));
  }

}
