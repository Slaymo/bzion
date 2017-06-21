var Chartist = require('chartist-webpack');

function buildWinLossDrawPieCharts() {
    var $pies = $('div[data-graph="pie-wld"]');

    $pies.each(function () {
        var $this = $(this);

        new Chartist.Pie(this, {
            series: [
                {
                    value: $this.data('wins'),
                    name: 'Wins',
                    className: 'ct-chart-series--wins'
                },
                {
                    value: $this.data('losses'),
                    name: 'Losses',
                    className: 'ct-chart-series--losses'
                },
                {
                    value: $this.data('draws'),
                    name: 'Draws',
                    className: 'ct-chart-series--draws'
                }
            ]
        });
    });
}

function buildLineCharts() {
    var $lines = $('div[data-graph="line"]');

    $lines.each(function() {
        var $this = $(this);
        new Chartist.Line(this, $this.data('chart'), {
            showPoint: false
        });
    });
}

module.exports = function() {
    // Due to hidden DOM objects not having a height and width, if the chart's parent is `display: none`, the chart won't
    // display. In this case, our charts may reside in tab panels, so we have to trigger a chart update when the tab is
    // clicked.
    //
    // see: https://github.com/gionkunz/chartist-js/issues/119

    $('[role="tab"]').on('shown.bzion.tab', function (event, tab) {
        var $tab = $(tab);

        $tab.find('.ct-chart').each(function (i, e) {
            e.__chartist__.update();
        });
    });

    // Start building all of the generic charts that we support
    buildWinLossDrawPieCharts();
    buildLineCharts();
};
