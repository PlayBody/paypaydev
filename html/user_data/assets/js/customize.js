/* カスタマイズ用Javascript */
// if(location.href !== 'https://ounodoukutsu.jp/shop_gunpla/products/list'){
//     const p = document.getElementById("logo");
//     p.classList.add("logo_not_index") 
// }
if(!(location.href.indexOf('list') > -1)){
    const p = document.getElementById("logo");
    p.classList.add("logo_not_index") 
}
if(location.href !== 'https://ounodoukutsu.jp/shop_gunpla/products/list'){
    const p = document.getElementById("ec-headerNaviRole");
    p.classList.add("ec-headerNaviRole") 
}
if(!(location.href.indexOf('list') > -1)){
    const p = document.getElementById("ec-headerNaviRole");
    p.classList.add("ec-headerNaviRole") 
}

jQuery(function($){
    $(function(){
    $('.slider').bxSlider();
    });
});


 //初期表示は非表示
 document.getElementById("p1").style.display ="none";
 const p1 = document.getElementById("p1");
  if(location.href  == "https://ounodoukutsu.jp/shop_gunpla/products/list"){
  	p1.style.display ="block";
  }
 
  document.getElementById("p2").style.display ="none";
 const p2 = document.getElementById("p2");
 if(location.href  == "https://ounodoukutsu.jp/shop_gunpla/products/list"){
 	p2.style.display ="block";
 }

        
$(function(){
  $('.your-class').slick({
        slidesToShow: 3,
  slidesToScroll: 1,
  autoplay: true,
  autoplaySpeed: 2000,
  });
});


jQuery(function($){
    $(".main_visual").slick({
	infinite: true,
        arrows:false,
	autoplaySpeed: 2200,
	speed: 1000,
        slidesToShow:1,
        slidesToScroll:1,
	fade: true,
	autoplay:true
    });
    $('.thumb').slick({
        asNavFor:'.main_visual',
        arrows:false,
	infinite: true,
        focusOnSelect: true,
        slidesToShow:6,
        slidesToScroll:1
    });
});