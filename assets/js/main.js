import $ from 'jquery';
import 'select2';

$(function () {
    require('./src/accordion')();
    require('./src/autocomplete')();
    require('./src/bans')();
    require('./src/tabs')();
    require('./src/menu')();
    require('./src/md-editor')();
    require('./src/charts')();
    require('./src/player-list')();
    require('./src/pills')();
    require('./src/profile')();
    require('./src/tables')();
    require('./src/select-objects')();
    require('./src/servers')();
});

// Export $ as jQuery for legacy JS scripts
global.$ = $;
