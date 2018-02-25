/*
jQuery è già installato da wordpress, quindi non serve aggiungerlo.
Unica nota è che non lo troverete assegnato alla variabile di window `$`, bensì alla variabile `jQuery`,
quindi per prima cosa si può assegnarlo alla variabile $ se siete abituati ad utilizzarlo così.
*/

//console.log("AAAA");
window.$ = window.jQuery;

jQuery(document).ready(function($) {
    
    $('.owl-carousel').owlCarousel({
        loop: true,
        margin: 10,
        nav: true,
        navText: [
          "<i class='fa fa-chevron-left'></i>",
          "<i class='fa fa-chevron-right'></i>"
        ],
        autoplay: true,
        autoplayHoverPause: true,
        responsive: {
          0: {
            items: 1
          },
          600: {
            items: 3
          },
          1000: {
            items: 5
          }
        }
      })

})


if (document.body.className.indexOf("home") !== -1) {
    var slideIndex = 1;
    showSlides(slideIndex);

    // Next/previous controls
    function plusSlides(n) {
    showSlides(slideIndex += n);
    }

    // Thumbnail image controls
    function currentSlide(n) {
    showSlides(slideIndex = n);
    }

    function showSlides(n) {
    var i;
    var slides = document.getElementsByClassName("mySlides");
    var dots = document.getElementsByClassName("dot");
    if (n > slides.length) {slideIndex = 1}
    if (n < 1) {slideIndex = slides.length}
    for (i = 0; i < slides.length; i++) {
        slides[i].style.display = "none";
    }
    for (i = 0; i < dots.length; i++) {
        dots[i].className = dots[i].className.replace(" active", "");
    }
    slides[slideIndex-1].style.display = "block";
    dots[slideIndex-1].className += " active";
    }
}

// Menu Indicator
var matches = [];
var searchEles = document.getElementById("header-menu").children;
var indicator = returnIndicator();
var numPage = document.getElementById("numPagina").value;
indicator.style.backgroundColor = window.getComputedStyle(searchEles[numPage], null).getPropertyValue("background-color");
//indicator.style.visibility = "hidden";
searchEles[numPage].appendChild(indicator);
document.getElementById("numPagina").style.visibility = "hidden";

document.getElementById("header-menu").addEventListener("mouseout", function( event ) {  

   // if ((document.body.className.indexOf("home") !== -1) && (event.currentTarget.className == -1)) {
        document.getElementById("s_indicator-menu").remove();
        var indicator = returnIndicator();
        var searchEles = document.getElementById("header-menu").children;
        indicator.style.backgroundColor = window.getComputedStyle(searchEles[numPage], null).getPropertyValue("background-color");
        searchEles[numPage].appendChild(indicator);

    //}

});

for(var i = 0; i < searchEles.length; i++) {
    searchEles[i].addEventListener("mouseover", function( event ) {  

        document.getElementById("s_indicator-menu").remove();
        var indicator = returnIndicator();
        //console.log(event.currentTarget.style.backgroundColor);
        
        indicator.style.backgroundColor = window.getComputedStyle(event.currentTarget, null).getPropertyValue("background-color");
        event.currentTarget.appendChild(indicator);
    });
}

function returnIndicator(){
    var indicator = document.createElement("div");
    indicator.className = "s_indicator-menu";
    indicator.id = "s_indicator-menu";
    //indicator.style.visibility = "hidden";
    return indicator;
}

// <div class="s_indicator-menu" id="s_indicator-menu"></div>