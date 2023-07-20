/**
 * @file
 * January custom js.
 *
 */
(function ($) {
$(document).ready(function () {
$('.profession-slider').slick({
    dots: true,
    infinite: false,
    speed: 300,
    arrows: false,
    slidesToShow: 3,
    slidesToScroll: 1,
    responsive: [
      {
        breakpoint: 992,
        settings: {
          slidesToShow: 2,
        }
      },
      {
        breakpoint: 576,
        settings: {
          centerMode: true,
          slidesToShow: 1,
        }
      },
      {
        breakpoint:400,
        settings: {
          centerMode:false,
          slidesToShow: 1,
        }
      },
    ]
  });
  $('.casestudy-slider').slick({
    dots: false,
    infinite: false,
    speed: 300,
    arrows: true,
    slidesToShow: 3,
    slidesToScroll: 1,
    responsive: [
      {
        breakpoint: 992,
        settings: {
          slidesToShow: 2,
        }
      },
      {
        breakpoint: 576,
        settings: {
          centerMode: true,
          slidesToShow: 1,
        }
      },
      {
        breakpoint:400,
        settings: {
          centerMode:false,
          slidesToShow: 1,
        }
      },
    ]
  });

  $('.testimonial-slider').slick({
    dots: true,
    infinite: false,
    speed: 300,
    arrows: false,
    slidesToShow: 2,
    slidesToScroll: 1,
    responsive: [
      {
        breakpoint: 576,
        settings: {
          slidesToShow: 1,
        }
      },
    ]
  });

  $('.blog-slider').slick({
    dots: true,
    infinite: false,
    speed: 300,
    arrows: false,
    slidesToShow: 3,
    slidesToScroll: 1,
    responsive: [
      {
        breakpoint: 992,
        settings: {
          slidesToShow: 2,
        }
      },
      {
        breakpoint: 576,
        settings: {
          centerMode: true,
          slidesToShow: 1,
        }
      },
      {
        breakpoint:400,
        settings: {
          centerMode:false,
          slidesToShow: 1,
        }
      },
    ]
  });

  $(window).scroll(function () {
    var scroll = $(window).scrollTop();
    if (scroll >= 155)
    {
      $("#header .navbar>.container").css({"padding":"10px 15px"});
      $("#header").css({"box-shadow":"0 0 5px rgba(0 0 0/25%)"});
      $("#top").css({"opacity":"1","visibility":"visible","bottom":"25px"});
    }
    else
    {
      $("#header .navbar>.container").css({"padding":"20px 15px"});
      $("#header").css({"box-shadow":"none"});
      $("#top").css({"opacity":"0","visibility":"hidden","bottom":"-25px"});
    }
  });

  $("a[href='#top']").click(function () {
    $("html, body").animate({ scrollTop: 0 }, "slow");
    return false;
  });

  $('.top-toolbar .views-exposed-form .form-actions').detach().prependTo('.top-toolbar .views-exposed-form .js-form-type-textfield');;
  $('.casestudy-action .addtoany_list a:first-child').append('<i>Share</i>');
  $(".tab-content.active").slideDown();
  $(".tab-content.active").fadeIn();
  $('.tab-nav-item').click(function () {
    $(".tab-content").removeClass('active');
    $(".tab-content[data-id='" + $(this).attr('data-id') + "']").addClass("active");
    if($(window).width() < 992)
    {
      $(".tab-content").slideUp();
      $(".tab-content[data-id='" + $(this).attr('data-id') + "']").slideDown();
    }
    else
    {
      $(".tab-content").hide().fadeOut();;
      $(".tab-content[data-id='" + $(this).attr('data-id') + "']").fadeIn();
    }
    $(".tab-nav-item").removeClass('active');
    $(".tab-nav-item[data-id='" + $(this).attr('data-id') + "']").addClass("active");
   });

  new WOW().init();

});
})(jQuery);
