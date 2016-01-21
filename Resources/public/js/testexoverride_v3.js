(function () {
    'use strict';

    var radarChartData = {};
    var ctx;
    var canvas;
    var userdata;
    var resourcedata;
    var widgetId;
    var radarcontent;

//    var grid = $('.grid-stack').data('gridstack');
    userdata        = $('#userresource').data('user');
    resourcedata    = $('#userresource').data('resource');
    console.log("userdata");console.log(userdata);
    console.log("resourcedata");console.log(resourcedata);
            //Get the data and draw the chart
            $('#showradar').on('click', function(){
                $.ajax({
                    type:"GET",
                    url: Routing.generate('ujm_paper_export_all_results_json_v3', {userdata:userdata, resourcedata:resourcedata}),
                    success: function(response) {
                        radarChartData = response;
                        console.log("response");
                        console.log(response);
                        setRadarChart(radarChartData);
                        $('.exportgraph').show();
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                    }
                });
            });



    //function for creating custom tooltip for datasets
    function annotateAllX(area,ctx,data,statData,posi,posj,othervars) {
        var retstring='<B><U>'+statData[posi][posj].v2+'</U></B><BR>';
        for(var i=data.datasets.length-1;i>=0;i--) {
            var boxLegend="<canvas id=\"canvas_Line"+posi+"_"+posj+"\" height=\"10\" width=\"30\" style=\"border:1px solid black;background:"+data.datasets[i].pointHighlightFill+"\">;</canvas>";
//console.log(data.datasets[i].pointHighlightFill);
            retstring=retstring+boxLegend+" "+statData[i][posj].datavalue+" "+statData[i][posj].v1+"<BR>";
        }
        return "<%='"+retstring+"'%>".replace(/<BR>/g," ");
    }

    function setRadarChart(radarChartData) {
        var options = {
            canvasBorders : false
            ,canvasBordersWidth : 3
            ,canvasBordersColor : "black"
            ,legend : true
            ,inGraphDataShow : true
            ,annotateDisplay : true
            ,annotateLabel: annotateAllX
            ,responsive: true
        };
        var myRadar = new Chart(
            document.getElementById('radaranalytics')
                .getContext("2d")
        ).Radar(radarChartData, options);
    }

    function resizeCanvas(htmlCanvas) {
        htmlCanvas.width = window.innerWidth;
        htmlCanvas.height = window.innerHeight;
        setRadarChart(radarChartData);
    }

    $('#exportgraph'+widgetId).on('click', function(){
        downloadCanvas(this, 'radaranalytics', 'radar.png');
    });

    function downloadCanvas(link, canvasId, filename) {
        link.href = document.getElementById(canvasId).toDataURL();
        link.download = filename;/**/
    }

}());