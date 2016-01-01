(function () {
    'use strict';

    var radarChartData = {};
    var ctx;
    var canvas;
    var userdata;
    var resourcedata;
    var widgetId;
    var radarcontent;

    //for each of the widget
    $('.radarcontent').each(function(index){
        widgetId = $(this).data('widget-instance');
        radarcontent = $(this);
        if (checkRadarAccess(widgetId))
        {
            console.log(widgetId);
            //show the list of statistic results
            $('#exo-statistics-results'+widgetId).on('click', function(){
                $.ajax({
                    type:"GET",
                    url: Routing.generate('ujm_paper_show_all_results', {resourcedata: resourcedata}),
                    success: function(response) {
                        $('#containerradardata'+widgetId).html(response);
                    },
                    error: function(jqXHR, textStatus, errorThrown) { }
                });
            });

            $(radarcontent).show();
            ///Get the data and Draw the chart
            $('#showcsv'+widgetId).on('click', function(){
                $.ajax({
                    type:"GET",
                    url: Routing.generate('ujm_paper_export_all_results_json', {userdata:userdata, resourcedata:resourcedata}),
                    success: function(response) {
                        radarChartData = response;
                        setRadarChart(radarChartData, widgetId);
                        //$('.exportgraph').show();
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                    }
                });
            });
        }
        else
        {
            $(radarcontent).hide();
            $('#radarmessage'+widgetId).html('Choose users and exercise in the widget parameters <span class="fa fa-pencil"></span> to display the graphics');
        }
    });

    function checkRadarAccess(widgetId)
    {
        //parameters to be sent to Chart

        userdata        = $('#userresource'+widgetId).data('user');
        resourcedata    = $('#userresource'+widgetId).data('resource');
console.log('widgetId'+widgetId);
console.log('userdata'+userdata);
console.log('resourcedata'+resourcedata);

        if (userdata != '' && resourcedata != '')
        {
            return true;
        }
        return false;
    }

    function setRadarChart(radarChartData, widgetId) {
        // legend for Chart
        var legend = "<ul style=\"list-style-type:none;\">";
        radarChartData.datasets.forEach(function (dataset) {
            legend += "<li><div style=\"background-color:" + dataset.strokeColor + ";height:0.5em;width:0.5em;float:left;margin-top:0.5em;margin-right:0.5em;\"></div><span style='font-family:Verdana;font-size: 12px;'>";
            if (dataset.label) {
                legend += dataset.label
            }
            legend += "</span></li>";
        });
        legend += "</ul>";

        var data = '<svg xmlns="http://www.w3.org/2000/svg">' +
            '<foreignObject width="100%" height="100%">' +
            '<div xmlns="http://www.w3.org/1999/xhtml" style="font-size:20px">' +
            legend +
            '</div></foreignObject></svg>';

        var DOMURL = window.URL || window.webkitURL || window;
        var img = new Image();
        img.setAttribute('crossOrigin', 'Anonymous');
        var svg = new Blob([data], {
            type: 'image/svg+xml;charset=utf-8'
        });

        var url = DOMURL.createObjectURL(svg);

        img.onload = function () {
            DOMURL.revokeObjectURL(url);

            //adds legend to chart
            Chart.types.Radar.extend({
                name: "RadarAlt",
                draw: function () {
                    this.scale.yCenter = this.chart.width / 2;
                    Chart.types.Radar.prototype.draw.apply(this, arguments);
                    this.chart.ctx.drawImage(img, 0, this.chart.width);
                }
            });

            // create chart
            /* can't use getContext on jQuery object. Must find undelying DOM object */
            //var ctx = $(chartcanvas)[0].getContext('2d');
            canvas = $('#radaranalytics'+widgetId);
            ctx = $(canvas)[0].getContext('2d');
            //canvas = document.getElementById("radaranalytics"+widgetId);
            //ctx = canvas.getContext('2d');
            var radar = new Chart(ctx).RadarAlt(radarChartData, {
                responsive: true,
                scaleShowLabels : true,
                multiTooltipTemplate: "<%= value %> - <%= datasetLabel %>"
            });
        };
//console.log(url);
        img.src = url;
    }

    function resizeCanvas(htmlCanvas) {
        htmlCanvas.width = window.innerWidth;
        htmlCanvas.height = window.innerHeight;
        setRadarChart(radarChartData);
    }

    $('#exportgraph'+widgetId).on('click', function(){
        downloadCanvas(this, 'radaranalytics'+widgetId, 'xxx.png');
    });

    function downloadCanvas(link, canvasId, filename) {
        link.href = document.getElementById(canvasId).toDataURL();
        link.download = filename;/**/
    }

}());