
var r = jQuery.noConflict();

function addTag(){

    if(!show_container) {
        r('#js-div-tag-container').css({display: "block"});
        var show_container = true;
    }
    var inpt = r('#custom-alt').val();
    //encode user input to html entity
    inpt = htmlEncode(inpt);
    //remove more that one spaces
    var splited_source = inpt.replace(/\s{2,}/g,' ').split(',');

    r.each(splited_source,function(index,value){

        if(value !== "") {
            var checkbox = r('<div class="js-seo-tag"><input type="checkbox" name="checkbox_alt[]" value="' + value + '" checked><span>' + value + '</span></div>');
            checkbox.appendTo('#js-div-tag-container');
            r('#custom-alt').val('').focus();
        }

    });

    return false;
}


function htmlEncode(value){
    //create a in-memory div, set it's inner text(which jQuery automatically encodes)
    //then grab the encoded contents back out.  The div never exists on the page.
    return r('<div/>').text(value).html();
}


r('#custom-alt').on('keypress ', function(e) {
    if(e.which == 13) {
      //  alert('heeey');
        e.preventDefault();
       addTag();
    }
});


