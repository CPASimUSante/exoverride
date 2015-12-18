(function () {
    'use strict';

    /*
     function getJsonStats()
     {
     $.ajax('admin/theme/delete/' + id)
     .done(function (data) {
     if (data === 'true') {
     window.location = home.path + 'admin/theme/list';
     } else {
     modal.fromRoute('claro_theme_error');
     }
     })
     .error(function () {
     modal.fromRoute('claro_theme_error');
     });
     }
     */
    var radarChartData = {
        labels: ["Eating", "Drinking", "Sleeping", "Designing", "Coding", "Cycling", "Running"],
        datasets: [{
            label: "My First dataset",
            fillColor: "rgba(220,220,220,0.2)",
            strokeColor: "rgba(220,220,220,1)",
            pointColor: "rgba(220,220,220,1)",
            pointStrokeColor: "#fff",
            pointHighlightFill: "#fff",
            pointHighlightStroke: "rgba(220,220,220,1)",
            data: [65, 59, 90, 81, 56, 55, 40]
        }, {
            label: "My Second dataset",
            fillColor: "rgba(151,187,205,0.2)",
            strokeColor: "rgba(151,187,205,1)",
            pointColor: "rgba(151,187,205,1)",
            pointStrokeColor: "#fff",
            pointHighlightFill: "#fff",
            pointHighlightStroke: "rgba(151,187,205,1)",
            data: [28, 48, 40, 19, 96, 27, 100]
        }]
    };

        // legend for Chart
        var legend = "<ul style=\"list-style-type:none;\">";
        radarChartData.datasets.forEach(function(dataset){
            legend += "<li><div style=\"background-color:" + dataset.strokeColor + ";height:0.5em;width:0.5em;float:left;margin-top:0.5em;margin-right:0.5em;\"></div><span style='font-family:Verdana;font-size: 12px;'>";
            if (dataset.label) {
                legend += dataset.label
            }
            legend += "</span></li>";
        })
        legend += "</ul>";

        var data = '<svg xmlns="http://www.w3.org/2000/svg">' +
            '<foreignObject width="100%" height="100%">' +
            '<div xmlns="http://www.w3.org/1999/xhtml" style="font-size:20px">' +
            legend +
            '</div>' +
            '</foreignObject>' +
            '</svg>';

        var DOMURL = window.URL || window.webkitURL || window;
        var img = new Image();
        var svg = new Blob([data], {
            type: 'image/svg+xml;charset=utf-8'
        });
        var url = DOMURL.createObjectURL(svg);

        img.onload = function() {
            DOMURL.revokeObjectURL(url);

            Chart.types.Radar.extend({
                name: "RadarAlt",
                draw: function() {
                    this.scale.yCenter = this.chart.width / 2;
                    Chart.types.Radar.prototype.draw.apply(this, arguments);
                    this.chart.ctx.drawImage(img, 0, this.chart.width);
                }
            });

            // create chart
            /* can't use getContext on jQuery object. Must find undelying DOM object */
            /* var chartcanvas = $('<canvas/>',{'class':'radaranalytics'})
             .prop({width: 300,height: 300});
             $(".radar-analytics").append(chartcanvas);*/
            //var ctx = $(chartcanvas)[0].getContext('2d');
            var ctx = document.getElementById("radaranalytics").getContext('2d');
            var radar = new Chart(ctx).RadarAlt(radarChartData, {
                responsive: true
            });
        }
        img.src = url;
}());