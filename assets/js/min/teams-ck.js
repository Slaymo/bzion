var pieChartCanvas=$("#teamMatchStats")[0].getContext("2d");if("undefined"!=typeof teamWins&&"undefined"!=typeof teamLosses&&"undefined"!=typeof teamDraws){var pieData=[{value:teamWins,color:"#EBEBEB"},{value:teamLosses,color:"#B6D1D8"},{value:teamDraws,color:"#8FDC00"}],pieOptions={segmentStrokeWidth:1,animation:!1};new Chart(pieChartCanvas).Pie(pieData,pieOptions)}else console.log("The follow variables were not defined on your page: teamWins, teamLosses, and teamDraws");