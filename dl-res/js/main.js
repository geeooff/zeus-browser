(function (document, $) {

	$.fn.removeClassPrefix = function (prefix) {
		this.each( function (index, element) {
			var classes = element.className.split(" ").map(function (className) {
				return className.indexOf(prefix) === 0 ? "" : className;
			});
			element.className = classes.join(" ");
		});
		return this;
	};

	$(document).ready(function(){

		var $carts = $(".cart");
		var $fullCarts = $carts.filter(".cart-full");
		var $lightCarts = $carts.filter(".cart-light");

		function refreshCartHtml() {
			if ($fullCarts.length > 0)
			{
				$.ajax({
					dataType: "html",
					url: "?fullcarthtml",
					success: function(data, textStatus, jqXHR){
						$fullCarts.html(data);
					},
					error: function(jqXHR, textStatus, errorThrown){
						console.error("".concat("Impossible de récupérer le fragment HTML complet du panier (", textStatus, ", ", errorThrown, ")"));
					}
				});
			}
			if ($lightCarts.length > 0)
			{
				$.ajax({
					dataType: "html",
					url: "?lightcarthtml",
					success: function(data, textStatus, jqXHR){
						$lightCarts.html(data);
					},
					error: function(jqXHR, textStatus, errorThrown){
						console.error("".concat("Impossible de récupérer le fragment HTML léger du panier (", textStatus, ", ", errorThrown, ")"));
					}
				});
			}
		}

		$("[data-toggle='tooltip']").tooltip();

		$(this).on("click", "a.btn.emptycart", function(e)
		{
			var $this = $(this);
			$this.addClass("active").prop("disabled", true);

			if (!confirm("Etes-vous sûr de vouloir vider votre panier ?"))
			{
				return false;
			}

			$.ajax({
				dataType: "json",
				url: $this.attr("href"),
				success: function(data, textStatus, jqXHR){
					refreshCartHtml();
				},
				error: function(jqXHR, textStatus, errorThrown){
					window.alert("".concat("Impossible de vider le panier (", textStatus, ", ", errorThrown, ")."));
				},
				complete: function(jqXHR, textStatus){
					$this.removeClass("active").prop("disabled", false);
				}
			});
			e.preventDefault();
		});

		$(this).on("click", "a.btn.addtocart", function(e)
		{
			var $this = $(this);
			$this.addClass("active").prop("disabled", true);

			$.ajax({
				dataType: "json",
				url: $this.attr("href"),
				success: function(data, textStatus, jqXHR){
					if (data == 2) {
						window.alert("Cet élement est déjà dans le panier.");
					}
					if (data != 0) {
						$this
							.removeClass("addtocart btn-outline-success")
							.addClass("removefromcart btn-outline-danger")
							.attr("href", "?removefromcart")
							.find("span")
								.text("Retirer");
					}
					refreshCartHtml();
				},
				error: function(jqXHR, textStatus, errorThrown){
					window.alert("".concat("Impossible d'ajouter l'élément au panier (", textStatus, ", ", errorThrown, ")."));
				},
				complete: function(jqXHR, textStatus){
					$this.removeClass("active").prop("disabled", false);
				}
			});
			e.preventDefault();
		});

		$(this).on("click", "a.btn.removefromcart", function(e)
		{
			var $this = $(this);
			$this.addClass("active").prop("disabled", true);

			$.ajax({
				dataType: "json",
				url: $this.attr("href"),
				success: function(data, textStatus, jqXHR){
					if (data == -1) {
						window.alert("Cet élement fait partie d'un dossier parent déjà ajouté au panier. Il ne peut être retiré individuellement.");
					}
					else {
						$this
							.removeClass("removefromcart btn-outline-danger")
							.addClass("addtocart btn-outline-success")
							.attr("href", "?addtocart")
							.find("span")
								.text("Ajouter");
					}
					refreshCartHtml();
				},
				error: function(jqXHR, textStatus, errorThrown){
					window.alert("".concat("Impossible d'ajouter l'élément au panier (", textStatus, ", ", errorThrown, ")."));
				},
				complete: function(jqXHR, textStatus){
					$this.removeClass("active").prop("disabled", false);
				}
			});
			e.preventDefault();
		});

		$(".mediainfo").each(function(index){
			var $this = $(this);
			var format = $this.is("pre") ? "text" : "html";
			$.ajax({
				dataType: format,
				url: "?mediainfo&format=" + format,
				success: function(data, textStatus, jqXHR){
					$this.removeClass("text-danger");
					if (format === "html")
						$this.html(data);
					else
						$this.text(data);
				},
				error: function(jqXHR, textStatus, errorThrown){
					var errorMessage = jqXHR.responseText || textStatus;
					window.alert("".concat("Impossible de récupérer les infos du média (", errorMessage, ")."));
					$this.text(errorMessage).addClass("text-danger");
				}
			});
		});

		$(this).on("change", "select.ratio", function(e){
			var $this = $(this);
			var $target = $(".embed-responsive");
			var className = "embed-responsive-".concat($this.val());
			$target.removeClassPrefix("embed-responsive-").addClass(className);
		});

		$(this).on("change", "select.ssl", function(e){
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
			info: false,
			paging: false,
			searching: false,
			processing: false,
			ordering: true,
			stateSave: true,
			stateDuration: 0,
			autoWidth: false
			/*columnDefs: [
				{
					targets: "_all",
					orderable: false,
					searchable: false
				},
				{
					targets: "col-name",
					searchable: true
				},
				{
					targets: [ "col-name", "col-date" ],
					orderable: true
				}
			],
			order: [
				[ "col-name", "asc" ]
			]*/
		});
	});

})(document, jQuery);

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