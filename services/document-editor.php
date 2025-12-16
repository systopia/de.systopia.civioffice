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

// phpcs:disable Drupal.Commenting.DocComment.ContentAfterOpen
/** @var \Symfony\Component\DependencyInjection\ContainerBuilder $container */

use Civi\Civioffice\DependencyInjection\Compiler\DocumentEditorTypePass;
use Civi\Civioffice\DocumentEditorManager;
use Civi\Civioffice\DocumentEditorType\CollaboraOnlineEditorType;
use Civi\Civioffice\DocumentEditorTypeContainer;
use Civi\Civioffice\DocumentEditorTypeInterface;
use Civi\Civioffice\EventSubscriber\CiviOfficeFilePageSubscriber;
use Civi\Civioffice\FileManager;
use Civi\Civioffice\FileManagerInterface;
use Civi\Civioffice\Wopi\Controller\CollaboraWopiFileController;
use Civi\Civioffice\Wopi\Discovery\WopDiscoveryServiceCacheDecorator;
use Civi\Civioffice\Wopi\Discovery\WopiDiscoveryService;
use Civi\Civioffice\Wopi\Discovery\WopiDiscoveryServiceInterface;
use Civi\Civioffice\Wopi\Request\CollaboraWopiRequestHandler;
use Civi\Civioffice\Wopi\UserInfoService;
use Civi\Civioffice\Wopi\Util\CiviUrlGenerator;
use Civi\Civioffice\Wopi\Validation\WopiProofValidator;
use Civi\Civioffice\Wopi\Validation\WopiRequestValidator;
use Civi\Civioffice\Wopi\WopiAccessTokenService;
use Symfony\Component\DependencyInjection\Reference;

$container->addCompilerPass(new DocumentEditorTypePass());

$container->autowire(DocumentEditorManager::class)
  ->setPublic(TRUE);

$container->autowire(FileManagerInterface::class, FileManager::class);

$container->autowire(CiviOfficeFilePageSubscriber::class)
  ->addTag('kernel.event_subscriber');

$container->autowire(CollaboraOnlineEditorType::class)
  ->addTag(DocumentEditorTypeInterface::class);

$container->autowire(CollaboraWopiFileController::class)
  ->setPublic(TRUE);

$container->autowire(WopiAccessTokenService::class)
  ->setArgument('$cryptoJwt', new Reference('crypto.jwt'));

$container->autowire(WopiDiscoveryServiceInterface::class, WopiDiscoveryService::class);

$container->autowire(WopDiscoveryServiceCacheDecorator::class)
  ->setArgument('$cache', new Reference('cache.long'))
  ->setDecoratedService(WopiDiscoveryServiceInterface::class);

$container->autowire(WopiRequestValidator::class);

$container->autowire(WopiProofValidator::class);

$container->autowire(CollaboraWopiRequestHandler::class);

$container->autowire(CiviUrlGenerator::class);

$container->autowire(UserInfoService::class);
