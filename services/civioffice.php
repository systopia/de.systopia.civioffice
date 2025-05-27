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

// phpcs:disable Drupal.Commenting.DocComment.ContentAfterOpen
/** @var \Symfony\Component\DependencyInjection\ContainerBuilder $container */

use Civi\Civioffice\Api4\Action\Civioffice\RenderWebAction;
use Civi\Civioffice\DependencyInjection\Compiler\Api4ActionPropertyAutowireFixPass;
use Civi\Civioffice\EventSubscriber\ActivityCiviOfficeTokenSubscriber;
use Civi\Civioffice\EventSubscriber\CaseCiviOfficeTokenSubscriber;
use Civi\Civioffice\EventSubscriber\CiviOfficeSearchKitTaskSubscriber;
use Civi\Civioffice\EventSubscriber\ContactCiviOfficeTokenSubscriber;
use Civi\Civioffice\EventSubscriber\ContributionCiviOfficeTokenSubscriber;
use Civi\Civioffice\EventSubscriber\EventCiviOfficeTokenSubscriber;
use Civi\Civioffice\EventSubscriber\MembershipCiviOfficeTokenSubscriber;
use Civi\Civioffice\EventSubscriber\ParticipantCiviOfficeTokenSubscriber;
use Civi\Civioffice\PhpWord\PhpWordTokenReplacer;
use Civi\Civioffice\Render\Queue\RenderQueueBuilderFactory;
use Civi\Civioffice\Render\Queue\RenderQueueRunner;
use Civi\Civioffice\Token\CiviOfficeTokenProcessor;
use Civi\Civioffice\Token\CiviOfficeTokenProcessorInterface;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;

if (!$container->has(\CRM_Queue_Service::class)) {
  $container->autowire(\CRM_Queue_Service::class, \CRM_Queue_Service::class);
}

$container->addCompilerPass(new Api4ActionPropertyAutowireFixPass(), PassConfig::TYPE_BEFORE_REMOVING);

$container->autowire(RenderQueueBuilderFactory::class)
  ->setPublic(TRUE);
$container->autowire(RenderQueueRunner::class)
  ->setPublic(TRUE);

$container->autowire(RenderWebAction::class)
  ->setPublic(TRUE)
  ->setShared(TRUE);

$container->autowire(PhpWordTokenReplacer::class);
$container->autowire(CiviOfficeTokenProcessorInterface::class, CiviOfficeTokenProcessor::class)
  ->setPublic(TRUE);

$container->register(\CRM_Civioffice_Tokens::class, \CRM_Civioffice_Tokens::class)
  ->setArgument('$entity', 'civioffice')
  ->addTag('kernel.event_subscriber')
  ->setLazy(TRUE);

$container->register(\CRM_Civioffice_Configuration::class, \CRM_Civioffice_Configuration::class)
  ->setFactory([\CRM_Civioffice_Configuration::class, 'getConfig'])
  ->addTag('kernel.event_subscriber');

if (interface_exists('\Civi\Mailattachment\AttachmentType\AttachmentTypeInterface')) {
  $container->register(\CRM_Civioffice_AttachmentProvider::class, \CRM_Civioffice_AttachmentProvider::class)
    ->addTag('kernel.event_subscriber');
}

$container->autowire(CiviOfficeSearchKitTaskSubscriber::class)
  ->addTag('kernel.event_subscriber');

$container->autowire(ActivityCiviOfficeTokenSubscriber::class)
  ->addTag('kernel.event_subscriber');
$container->autowire(CaseCiviOfficeTokenSubscriber::class)
  ->addTag('kernel.event_subscriber');
$container->autowire(ContactCiviOfficeTokenSubscriber::class)
  ->addTag('kernel.event_subscriber');
$container->autowire(ContributionCiviOfficeTokenSubscriber::class)
  ->addTag('kernel.event_subscriber');
$container->autowire(EventCiviOfficeTokenSubscriber::class)
  ->addTag('kernel.event_subscriber');
$container->autowire(MembershipCiviOfficeTokenSubscriber::class)
  ->addTag('kernel.event_subscriber');
$container->autowire(ParticipantCiviOfficeTokenSubscriber::class)
  ->addTag('kernel.event_subscriber');
