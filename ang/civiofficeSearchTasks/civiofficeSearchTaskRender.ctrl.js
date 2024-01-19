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

(function(angular, $, _) {
  "use strict";

  angular.module('civiofficeSearchTasks').controller('civiOfficeSearchTaskRender', function($scope, crmApi4, dialogService) {
    const ts = $scope.ts = CRM.ts('civioffice');
    const model = $scope.model;
    const ctrl = this;

    crmApi4('CiviofficeRenderer', 'get', {
      where: [["is_active", "=", true]],
      orderBy: {name: 'ASC'},
    }).then((result) => {
      $scope.renderer = result[0] || null;
      ctrl.renderers = result;
    });

    crmApi4('CiviofficeActivityType', 'get', {
      entityType: model.entityInfo.name,
      select: ['id', 'label', 'is_last_used'],
      orderBy: {label: 'ASC'},
    }).then((result) => {
      $scope.activityTypeId = _.result(_.find(result, {is_last_used: true}), 'id');
      ctrl.activityTypes = result;
    });

    $scope.liveSnippets = {};
    crmApi4('CiviofficeLiveSnippet', 'get', {
      select: ['name', 'label', 'description', 'last_value'],
      orderBy: {name: 'ASC'},
    }).then((result) => {
      for (const liveSnippet of result) {
        $scope.liveSnippets[liveSnippet.name] = liveSnippet.last_value;
      }
      ctrl.liveSnippets = result;
    });

    $scope.$watch('renderer', function(renderer) {
      if (!renderer) {
        ctrl.documents = [];
        $scope.mimeType = null;
      }
      else {
        crmApi4('CiviofficeDocument', 'get', {
          where: [['mime_type', 'IN', renderer.supported_mime_types]],
          orderBy: {name: 'ASC'},
        }).then((result) => ctrl.documents = result);

        if (!renderer.supported_output_mime_types.includes($scope.mimeType)) {
          $scope.mimeType = renderer.supported_output_mime_types[0] || null;
        }
      }
    });

    this.cancel = function() {
      dialogService.cancel('crmSearchTask');
    };

    this.render = function() {
      ctrl.run = true;
      $('.ui-dialog-titlebar button').hide();
      crmApi4('Civioffice', 'renderWeb', {
        entityType: model.entityInfo.name,
        entityIds: model.ids,
        rendererUri: $scope.renderer.uri,
        documentUri: $scope.documentUri,
        mimeType: $scope.mimeType,
        activityTypeId: $scope.activityTypeId,
        liveSnippets: $scope.liveSnippets,
      }).then((result) => {
        window.open(result.redirect);
        dialogService.close('crmSearchTask');
      }, (failure) => {
        if (failure.error_message) {
          CRM.alert(ts(
            'An error occurred while attempting to create documents with CiviOffice: %1',
            {1: failure.error_message}
          ), ts('Error'), 'error');
        }
        else {
          CRM.alert(ts(
            'An error occurred while attempting to create documents with CiviOffice.'
          ), ts('Error'), 'error');
        }
        dialogService.close('crmSearchTask');
      });
    };
  });
})(angular, CRM.$, CRM._);
