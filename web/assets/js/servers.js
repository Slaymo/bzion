function initPage() {
    // Hide any dimmers that might have been left
    $(".dimmer, .spinner").fadeOut('fast');

    var dimmer  = $("<div/>").hide().addClass("dimmer");
    var spinner = $("<div/>").hide().addClass("spinner").text("Loading...");

    // Add an invisible dimmer to elements that don't have one yet
    $(".dimmable").prepend(dimmer).prepend(spinner);

    $(".c-servers__server").each(function() {
        var url = baseURLNoHost + "/servers/" + $(this).attr("data-id");
        $(this).updateServer(url);
    });
}

$(document).ready(function() {
    initPage();
});

$(".c-servers").on("click", ".server-refresh", function(event) {
    event.preventDefault();

    $(this).parents(".c-servers__server").updateServer($(this).attr("href"));
});

$.fn.updateServer = function(url) {
    var server = $(this);

    server.startSpinners().find(".c-servers__server__info").load(url, function() {
        server.stopSpinners();
    });
};

$.fn.startSpinners = function() {
    $(this).children(".dimmable").children(".dimmer, .spinner").fadeIn('fast');
    return this;
};

$.fn.stopSpinners = function() {
    this.children(".dimmable").children(".dimmer, .spinner").fadeOut('fast');
    return this;
};
