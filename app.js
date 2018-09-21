angular.module('app', ['ui.router'])
    .directive('tasks', function () {
        function taskStatusSort(a, b) {
            if (a.trackerStatus && b.trackerStatus) {
                return a.trackerStatus > b.trackerStatus ? -1 : 1;
            }
            if (a.trackerStatus) return -1;
            if (b.trackerStatus) return 1;

            if (a.priority === b.priority) {
                if (a.status === b.status) {
                    return a.title > b.title ? 1 : -1;
                }

                return a.status > b.status ? 1 : -1;
            }
            return a.priority > b.priority ? -1 : 1;
        }
        function mapTask(task) {
            switch (task.status) {
                case 'error':
                    task.priority = 10;
                    break;
                case 'waiting':
                case 'finishing':
                case 'filehosting_waiting':
                case 'hash_checking':
                case 'extracting':
                    task.priority = 6;
                    break;

                case 'paused':
                case 'downloading':
                case 'finished':
                    task.priority = 4;
                    break;

                case 'seeding':
                    task.priority = 0;
                    break;

                default:
                    task.priority = 1;
                    break;
            }

            task.trackerStatus = '';
            (task.additional.tracker || []).forEach(function (tracker) {
                if (task.trackerStatus !== tracker.status && tracker.status !== 'Success')
                    task.trackerStatus = tracker.status;
            });

            return task;
        }

        return {
            restrict: 'E',
            replace: true,
            controller: ['$scope', '$http', function ($scope, $http) {
                function tasks() {
                    $http.get('/?tasks')
                        .then(function (response) {
                            var tasks = response.data.data.tasks.map(mapTask);
                            tasks.sort(taskStatusSort);
                            $scope.tasks = tasks;
                        })
                        .catch(function (err) {
                            console.log(err);
                        });
                }

                tasks();
                setInterval(tasks, 10000);
            }],
            template: '<ul>' +
                '<li ng-repeat="task in tasks track by task.id" ng-class="{\'tracker-error\':!!task.trackerStatus}">' +
                '<span title>{{task.title}} {{task.trackerStatus}}</span>' +
                '<span ratio ng-if="task.size==0">0 %</span>' +
                '<span ratio ng-if="task.size!==0 && task.additional.transfer.size_downloaded===task.size">100 %</span>' +
                '<span ratio ng-if="task.size!==0 && task.additional.transfer.size_downloaded!==task.size">{{(task.additional.transfer.size_downloaded/task.size*100.0)|number: 2}} %</span>' +
                '<span status>{{task.status}}</span>' +
                '</li>' +
                '</ul>'
        }
    })
    .directive('config', function () {
        return {
            restrict: 'E',
            templateUrl: 'config.html',
            controller: function ($scope) {
                console.log('config running')
            }
        }
    });