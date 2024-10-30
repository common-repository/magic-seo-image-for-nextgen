/**@license
This file uses Google Suggest for jQuery plugin (licensed under GPLv3) by Haochi Chen ( http://ihaochi.com )
 */

var jq = jQuery.noConflict();

jq.fn.googleSuggest = function(opts){
  extra_opts = {
    minLength: 2,
  }
  opts = jq.extend({service: 'web', secure: false, client: 'psy', ds: '' }, opts,extra_opts);

  opts.source = function(request, response){
    jq.ajax({
      url: 'http'+(opts.secure?'s':'')+'://clients1.google.com/complete/search',
      dataType: 'jsonp',
      data: {
        q: request.term,
        nolabels: 't',
        client: opts.client,
        ds: opts.ds
      },
      success: function(data) {
        response(jq.map(data[1], function(item){
          return { value: jq("<span>").html(item[0]).text() };
        }));
      }
    });
  };

  return this.each(function(){
    jq(this).autocomplete(opts);
  });
}
