//$.fn.tooltip.Constructor.DEFAULTS.container = "body";
//$.fn.tooltip.Constructor.DEFAULTS.placement = "auto";

$(document).ready(function(){

	var $carts = $(".cart");
	var $fullCarts = $carts.filter(".cart-full");
	var $lightCarts = $carts.filter(".cart-light");
	
	function refreshCartHtml() {
		if ($fullCarts.length > 0)
		{
			$.ajax({
				"dataType": "html",
				"url": "?fullcarthtml",
				"success": function(data, textStatus, jqXHR){
					$fullCarts.html(data);
				},
				"error": function(jqXHR, textStatus, errorThrown){
					console.error("".concat("Impossible de récupérer le fragment HTML complet du panier (", textStatus, ", ", errorThrown, ")"));
				}
			});
		}
		if ($lightCarts.length > 0)
		{
			$.ajax({
				"dataType": "html",
				"url": "?lightcarthtml",
				"success": function(data, textStatus, jqXHR){
					$lightCarts.html(data);
				},
				"error": function(jqXHR, textStatus, errorThrown){
					console.error("".concat("Impossible de récupérer le fragment HTML léger du panier (", textStatus, ", ", errorThrown, ")"));
				}
			});
		}
	}

	$("[data-toggle='tooltip']").tooltip();

	$("a.btn.emptycart").on("click", function(e)
	{
		var $this = $(this);
		$this.addClass("active").prop("disabled", true);

		if (!confirm("Etes-vous sûr de vouloir vider votre panier ?"))
		{
			return false;
		}

		$.ajax({
			"dataType": "json",
			"url": $this.attr("href"),
			"success": function(data, textStatus, jqXHR){
				refreshCartHtml();
			},
			"error": function(jqXHR, textStatus, errorThrown){
				window.alert("".concat("Impossible de vider le panier (", textStatus, ", ", errorThrown, ")."));
			},
			"complete": function(jqXHR, textStatus){
				$this.removeClass("active").prop("disabled", false);
			}
		});
		e.preventDefault();
	});

	$("a.btn.addtocart").on("click", function(e)
	{
		var $this = $(this);
		$this.addClass("active").prop("disabled", true);

		$.ajax({
			"dataType": "json",
			"url": $this.attr("href"),
			"success": function(data, textStatus, jqXHR){
				if (data == 2) {
					window.alert("Cet élement est déjà dans le panier.");
				}
				$("i.fa", $this).removeClass("fa-shopping-cart").addClass("fa-check");
				refreshCartHtml();
			},
			"error": function(jqXHR, textStatus, errorThrown){
				window.alert("".concat("Impossible d'ajouter l'élément au panier (", textStatus, ", ", errorThrown, ")."));
			},
			"complete": function(jqXHR, textStatus){
				$this.removeClass("active").prop("disabled", false);
			}
		});
		e.preventDefault();
	});

	$("a.btn.removefromcart").on("click", function(e)
	{
		var $this = $(this);
		$this.addClass("active").prop("disabled", true);

		$.ajax({
			"dataType": "json",
			"url": $this.attr("href"),
			"success": function(data, textStatus, jqXHR){
				if (data == -1) {
					window.alert("Cet élement fait partie d'un dossier parent déjà ajouté au panier. Il ne peut être retiré individuellement.");
				}
				$("i.fa", $this).removeClass("fa-shopping-cart").addClass("fa-check");
				refreshCartHtml();
			},
			"error": function(jqXHR, textStatus, errorThrown){
				window.alert("".concat("Impossible d'ajouter l'élément au panier (", textStatus, ", ", errorThrown, ")."));
			},
			"complete": function(jqXHR, textStatus){
				$this.removeClass("active").prop("disabled", false);
			}
		});
		e.preventDefault();
	});

	$(".mediainfo").each(function(index){
		var $this = $(this);
		var format = $this.is("pre") ? "text" : "html";
		$.ajax({
			"dataType": format,
			"url": "?mediainfo&format=" + format,
			"success": function(data, textStatus, jqXHR){
				$this.removeClass("text-danger");
				if (format === "html")
					$this.html(data);
				else
					$this.text(data);
			},
			"error": function(jqXHR, textStatus, errorThrown){
				var errorMessage = jqXHR.responseText || textStatus;
				window.alert("".concat("Impossible de récupérer les infos du média (", errorMessage, ")."));
				$this.text(errorMessage).addClass("text-danger");
			}
		});
	});

	$("select.ratio").on("change", function(e){
		var $this = $(this);
		var $target = $(".embed-responsive");
		switch($this.val()) {
			case "auto": $target.removeClass("embed-responsive-4by3 embed-responsive-16by9").addClass("embed-responsive-auto"); break;
			case "16by9": $target.removeClass("embed-responsive-auto embed-responsive-4by3").addClass("embed-responsive-16by9"); break;
			case "4by3": $target.removeClass("embed-responsive-auto embed-responsive-16by9").addClass("embed-responsive-4by3"); break;
		}
	});
	
	$("select.ssl").on("change", function(e){
		var $this = $(this);
		var $target = $("#player");
		var src = $this.val();
		if ($target.is("video")) {
			$('source[type^="video"]', $target).attr("src", src);
			$target.each(function(index){
				this.load();
			});
		}
		else if ($target.is("audio")) {
			$('source[type^="audio"]', $target).attr("src", src);
			$target.each(function(index){
				this.load();
			});
		}
	});

	$("#dir").DataTable({
		"info": false,
		"paging": false,
		"searching": false,
		"processing": false,
		"stateSave": true,
		"stateDuration": 0,
		"columnDefs": [
			{
				"targets": [ 0, 2, 3 ],
				"searchable": false
			},
			{
				"targets": [ 4 ],
				"orderable": false,
				"searchable": false
			}
		],
		"order": [
			[ 1, "asc" ]
		]
	});
});

// window['__onGCastApiAvailable'] = function(loaded, errorInfo) {
// 	if (loaded) {
// 		initializeCastApi();
// 	} else {
// 		console.log(errorInfo);
// 	}
// };

// initializeCastApi = function() {
// 	var sessionRequest = new chrome.cast.SessionRequest(chrome.cast.media.DEFAULT_MEDIA_RECEIVER_APP_ID);
// 	var apiConfig = new chrome.cast.ApiConfig(
// 		sessionRequest,
// 		sessionListener,
// 		receiverListener
// 	);
// 	chrome.cast.initialize(apiConfig, onInitSuccess, onError);
// };

// function receiverListener(e) {
// 	if( e === chrome.cast.ReceiverAvailability.AVAILABLE) {

// 	}
// }

// function onMediaDiscovered(how, media) {
// 	media.addUpdateListener(onMediaStatusUpdate);
// }

// function sessionListener(e) {
// 	session = e;
// 	if (session.media.length != 0) {
// 		onMediaDiscovered('onRequestSessionSuccess', session.media[0]);
// 	}
// }