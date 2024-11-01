/**
 * Public VisideaWoocommerce class.
 * This will be generated on every page (cart, shop, product),
 * as there are no WooCommerce SDK for checking which page it is (and for syle).
 *
 * This class will be initialized from PHP, initializing it with the
 * current page, engine_url, engine_id and, if auth is needed, public tokens.
 *
 * It will then load necessary classess and styles, waiting for .recommend()
 * to be called. That method will render, hypothetically diffrently, product
 * on each page.
 *
 */
 class VisideaWoocommerce {

  /**
   *    Constructor for VisideaWoocommerce Objects.
   *
   *    This methods initializes the fields of this object, loads
   *    used scripts and styles, initializes VisideaAPI handles
   *    and calls functions to send messages to Visidea servers.
   *    Not all combinations of page and actions are well formed, for example
   *    'cart' and 'view' have no sense together.
   *
   *    @param {?string} page    one of {'home', 'product', 'cart'}, identifies current page
   *    @param {string} action  one of {'view', 'cart', 'purchase'}, identifies performed action
   *    @param {string} shop    UID of this shop
   *    @param {string} public_token
   *    @param {string} user_id ID of the current user in the context of the shop
   *    @param {?array of string} item_id single item if action is 'view' or
   *    current page is 'product', multiple items if action is 'purchase'
   *
   *
   */
  constructor(shop, public_token) {

      // Initialize fields
      this.shop = shop;
      this.public_token = public_token;
      this.visidea = {};
      this.user_id = 0;

      this.whenExists('Visidea', function() {
        // Initialize visidea
        this.visidea = new Visidea.Api(this.shop, this.public_token);

        // Load necessary scripts and styles
        this.loadCss(`
            @font-face
            {
                font-family: 'slick';
                font-weight: normal;
                font-style: normal;
                src: url('https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/fonts/slick.eot');
                src: url('https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/fonts/slick.eot?#iefix') format('embedded-opentype'), url('https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/fonts/slick.woff') format('woff'), url('https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/fonts/slick.ttf') format('truetype'), url('https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/fonts/slick.svg#slick') format('svg');
            }
            .visidea {
                margin: 2em 0;
            }
            .visidea h2 {
                text-align: center;
                margin-bottom: 1rem;
            }
            .visidea a {
                outline: 0;
                color:black;
            }
            .visidea p {
                margin:0;
            }
            .visidea .visidea__product {
                padding: 0px 0.5rem;
                text-align: center;
                outline: 0;
                line-height: 1.5;
            }
            .visidea .visidea__product-heading, .visidea .visidea__product-brand {
                margin: 0;
            }
            .visidea__product-price del {
                display: block;
                // margin-right:1rem;
            }
            .visidea img.visidea__product-image {
                max-width: 100%;
            }
            .visidea .slick-next:before, .visidea .slick-prev:before {
                color: #000!important;
                opacity: .25;
            }
            .slick-next, .slick-prev {
                z-index: 9999;
                display: inline-block!important;
                overflow: hidden;
            }
            .slick-prev {
                left: 5px;
                float: left;
                margin: 0;
                text-indent: 0;
            }
            .slick-next {
                right: 5px;
                float: left;
                margin: 0;
                text-indent: 0;
            }
            .woocommerce.widget_product_search {
                white-space: nowrap;
            }
            .visidea-visualsearch-icon {
                display: inline-block;
                float:left;
                width:40px;
                margin-right: 10px;
            }
            .visidea-visualsearch-icon img {
                margin-top: 3px;
                width:40px;
                height:40px;
            }
            .woocommerce-product-search {
                display: inline-block;
            }
            .visidea-visualsearch {
                display:none;
                background:white;
                position:fixed;
                z-index: 99999;
                top:0;
                bottom:0;
                right:0;
                left:0;
            }
            .visidea-visualsearch p {
                margin:0;
            }
            .visidea-visualsearch a {
                color:black;
            }
            .visidea-visualsearch__exit {
                z-index: 99999;
                position: absolute;
                right: 0;
                float:right;
                background-image:url("https://cdn.visidea.ai/imgs/icons/svg/visidea_cancelcircle.svg");
                width:30px;
                height:30px;
                margin:13px;
            }
            .visidea-visualsearch__exit:hover {
                cursor: pointer;
            }
            .visidea-visualsearch__upload {
                border: dashed 2px #000;
                margin: 15px;
                padding: 15px;
                text-align:center;
            }
            .visidea-visualsearch__upload-photo {
                background:url("https://cdn.visidea.ai/imgs/icons/svg/visidea_camera.svg");
                width:40px;
                height:40px;
                margin:auto;
                margin-botton:15px;
            }
            .visidea-visualsearch__upload-photo:hover {
                cursor: pointer;
            }
            .visidea-visualsearch__upload-input {
                width: 0px;
                height: 0px;
                overflow: hidden;
                display: none;
            }
            .visidea-visualsearch__container {
                display: block;
                margin: 15px;
                margin-top: 0px;
            }
            .visidea-visualsearch__nav {
                display: block;
                width: 100%;
                margin-right: 15px;
                margin-bottom: 15px;
            }
            .visidea-visualsearch__nav-content {
                position: relative;
                display: block;
                width: 150px;
                margin: auto;
            }
            .visidea-visualsearch__content {
                display:flex;
                flex-direction: column;
                width:100%;
                overflow:auto;
            }
            .visidea-visualsearch__upload-image {
                width: 100%!important;
            }
            #visidea-visualsearch__upload-canvas {
                position: absolute;
                top: 0;
                left: 0;
                margin-top: 0;
                margin-left: 0;
            }
            #visidea-visualsearch__upload-canvas-crop {
                width: 800px;
                height: 800px;
                display:none;
            }
            .visidea-visualsearch .visidea__product {
                width:50%;
                float:left;
                margin-bottom:15px;
                line-height: 1.5;
            }
            .visidea-visualsearch .visidea__product-caption {
                text-align: center;
            }
            .visidea-visualsearch .visidea__product:nth-child(2n+1){
                clear:left;
            }
            @media only screen and (min-width: 768px) {
                .visidea-visualsearch__container {
                    display: flex;
                }
                .visidea-visualsearch__upload-photo {
                    width:150px;
                    height:150px;
                }
                .visidea-visualsearch__nav {
                    display: flex;
                    flex-direction: column;
                    width: 220px;
                }
                .visidea-visualsearch__nav-content {
                    display: inline-block;
                    width: 220px;
                    margin: 0;
                }
                .visidea-visualsearch__exit {
                  margin: 10px;
                }
            }
            @media only screen and (min-width: 1024px) {
                .visidea-visualsearch .visidea__product {
                    width:25%;
                }
                .visidea-visualsearch .visidea__product:nth-child(2n+1){
                    clear:none;
                }
                .visidea-visualsearch .visidea__product:nth-child(4n+1){
                    clear:left;
                }
                .visidea-visualsearch__upload {
                    margin: 50px;
                    padding: 50px;
                }
                .visidea-visualsearch__container {
                    margin: 50px;
                }
            }
        `);

        this.addCustomCss();
        this.initializeVisualSearch();

      }.bind(this));

  }


  /**
   * 
   *    Add custom css
   *
   */
  async addCustomCss() {
    // Wait for 'visidea.conf' to exist
    while (!this.visidea.conf || !this.visidea.conf.currency){
      //console.log('wait');
      await new Promise(r => setTimeout(r,10))
    }
    if (this.visidea.conf.css && this.visidea.conf.css != '')
      this.loadCss(this.visidea.conf.css)
  }


  /**
   *    visual search initialization method for VisideaWoocommerce Objects.
   *
   *    This methods initializes the visual search
   *
   */
  async initializeVisualSearch() {

      // Wait for 'visidea.conf' to exist
      while (!this.visidea.conf || !this.visidea.conf.currency){
        //console.log('wait');
        await new Promise(r => setTimeout(r,10))
      }

      if (!this.visidea.conf.has_visualsearch)
        return;

      var icon = '<svg id="Livello_1" data-name="Livello 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40"><path d="M32.41082,7.60858a17.528,17.528,0,1,0-2.61108,26.92279,3.41827,3.41827,0,1,0-1.25586-1.56732A15.52619,15.52619,0,1,1,35.34149,22.4455a1.0004,1.0004,0,1,0,1.97656.31054A17.60924,17.60924,0,0,0,32.41082,7.60858Zm-1.627,23.03027a1.40822,1.40822,0,0,1,.918-.33984c.03613,0,.07324.002.11035.00488a1.39761,1.39761,0,0,1,.96484.48926,1.41453,1.41453,0,0,1-.15332,1.99317v.001a1.41428,1.41428,0,0,1-1.83984-2.14844Z"/><path d="M19.82728,16.32285a3.90561,3.90561,0,1,0,3.90564,3.90564A3.90558,3.90558,0,0,0,19.82728,16.32285Zm0,5.3974a1.49179,1.49179,0,1,1,1.49182-1.49176A1.49183,1.49183,0,0,1,19.82728,21.72025Z"/><path d="M27.16382,13.33341H24.04664l-1.81348-1.49414a2.00026,2.00026,0,0,0-1.27344-.458H18.6941a1.99318,1.99318,0,0,0-1.27149.457l-1.8164,1.49511H12.491a2.0026,2.0026,0,0,0-2,2v9.79a2.0026,2.0026,0,0,0,2,2H27.16382a2.0026,2.0026,0,0,0,2-2v-9.79A2.0026,2.0026,0,0,0,27.16382,13.33341Zm-3.10546.001h0Zm3.10546,11.78906H12.491v-9.79h3.11524a2.00523,2.00523,0,0,0,1.27246-.45605l1.81543-1.49609h2.2666l1.81543,1.49609a2.00218,2.00218,0,0,0,1.27148.45605h3.11621Z"/></svg>';
      var html = '<div class="visidea-visualsearch-icon"><a href="javascript:visideaWoocommerce.showVisualSearch()">'+icon+'</a></div>';

      if (this.visidea.conf.visualsearch_show_after && this.visidea.conf.visualsearch_show_after != '')
          jQuery(this.visidea.conf.visualsearch_show_after).after(html);
      else {
        if (jQuery('.widget_product_search').length)
          jQuery('.widget_product_search').prepend(html);
        if (jQuery('.ast-header-search').length)
          jQuery('.ast-header-search').after(html);
      }
      
      if (this.user_id == 0) {
          var visidea_user_id = localStorage.getItem('visidea_user_id');
          if (!visidea_user_id) {
              visidea_user_id = this.visidea.uuidv4();
              localStorage.setItem('visidea_user_id', visidea_user_id);
          }
          this.user_id = visidea_user_id;
      }

      var html = '<div class="visidea-visualsearch" id="visidea-visualsearch">';
      html = html + '<a class="visidea-visualsearch__exit" onclick="visideaWoocommerce.hideVisualSearch()"></a>';
      html = html + '<div id="visidea-vs-root" website="'+this.shop+'" public_token="'+this.public_token+'" user_id="'+this.user_id+'"></div>';      
      html = html + '</div>';

      jQuery('body').append(html);

      this.loadScript('https://cdn.visidea.ai/visual-search/js/main.js?ver=1.3.0');

  }


  showVisualSearch() {
      jQuery('.visidea-visualsearch').css('display','block');
      jQuery('html').scrollTop(0);
      this.fixContentHeight();
      jQuery('body').css('overflow-y','hidden');
  }

  fixContentHeight() {
      var width = jQuery('html').width();
      var height = jQuery('.visidea-visualsearch').height();
      var topheight = jQuery('.visidea-visualsearch__upload').height();
      var contentheight = height-topheight-210;
      if (width >= 768 && width < 1024) {
          contentheight += 120;
      }
      if (width < 768) {
          var navheight = jQuery('.visidea-visualsearch__nav').height();
          contentheight -= navheight;
          contentheight += 110;
      }
      jQuery('.visidea-visualsearch__content').css('height',contentheight+'px');

  }

  hideVisualSearch() {
      jQuery('.visidea-visualsearch').css('display','none');
      jQuery('body').css('overflow-y','auto');

      const url = new URL(window.location);
      url.searchParams.delete('visidea');
      url.searchParams.delete('visideaitem');
      window.history.pushState({}, '', url);
  }


  /**
   *    Interaction method for VisideaWoocommerce Objects.
   *
   *    This methods initializes the fields of this object
   *    and calls functions to send messages to Visidea servers.
   *    Not all combinations of page and actions are well formed, for example
   *    'cart' and 'view' have no sense together.
   *
   *    @param {?string} page    one of {'home', 'product', 'cart'}, identifies current page
   *    @param {string} action  one of {'view', 'cart', 'purchase'}, identifies performed action
   *    @param {string} user_id ID of the current user in the context of the shop
   *    @param {?array of string} item_ids single item if action is 'view' or
   *    current page is 'product', multiple items if action is 'purchase'
   */
  async interaction(page, action, user_id, item_ids) {

      // Wait for 'visidea.conf' to exist
      while (!this.visidea.conf){
        //console.log('wait');
        await new Promise(r => setTimeout(r,10))
      }

      // console.log('interaction')

      // Initialize fields
      this.page = page;
      this.action = action;
      this.user_id = user_id;
      this.item_ids = item_ids;
      this.product_id = (item_ids == null)?null:item_ids[0];
      // this.visidea = {};

      if (this.user_id == 0) {
          var visidea_user_id = localStorage.getItem('visidea_user_id');
          if (!visidea_user_id) {
              visidea_user_id = this.visidea.uuidv4();
              localStorage.setItem('visidea_user_id', visidea_user_id);
          }
          this.user_id = visidea_user_id;
      }

      // Send messages to Visidea servers
      // based on the current action
      if (this.action == 'view') {
        this.visidea.item_view(this.product_id, this.user_id, null);
      } else if (this.action == 'cart'){
        this.visidea.item_cart(this.product_id, this.user_id, null);
      } else if (this.action == 'purchase'){
        // Cycle through all the items in the given array
        for (var item_id of this.item_ids) {
          this.visidea.item_purchase(item_id, this.user_id, null);
        }
      }

  }


  /**
  * Method to load a style given its url
  * @param {string} src   url of style to load
  */
  loadStyle(src) {
    var link = document.createElement('link');
    link.href = src;
    link.type = "text/css";
    link.rel = "stylesheet";
    link.media = "screen,print";
    document.head.appendChild(link);
  }


  /**
  * Method to load inline CSS
  * @param {string} styles string of CSS to load
  */
  loadCss(styles) {
    var styleSheet = document.createElement("style");
    styleSheet.type = "text/css";
    styleSheet.innerHTML = styles;
    document.head.appendChild(styleSheet);
  }


  /**
  * Method to load a script given its url
  * @param {string} src url of script to load
  */
  loadScript(src) {
    var script = document.createElement('script');
      script.onload = function () {
       //do stuff with the script
    };
    script.src = src;
    document.head.appendChild(script);
  }


  /**
  * Create inline CSS for a product
  * @param {string}   title   name of the product
  * @param {?string}  brand   brand of the product
  * @param {string}   link    link to the page of the product
  * @param {string}   image   link to the image of the product
  * @param {string}   price   price of the product
  */
  renderProduct(elem, product_id, title, brand, link, image, price, market_price){
    //link = link.replace('/en/','/'+MadcommerceAPI.config.language+'/');
    if (!title)
      title = "";
    if (!brand)
      brand = "";
    if (!link)
      link = "";
    if (!image)
      image = "";
    if (!price)
      price = "";

    let priceString = '<p class="visidea__product-price">';
    if (price != market_price)
      priceString += '<del>' + this.visidea.format_currency(price) + '</del>';
    priceString += '<strong>' + this.visidea.format_currency(market_price) + '</strong>';
    priceString += '</p>';

    let cartString = '';
    if (elem.cartbtn) {
      let cartLink = window.location.href;
      if (jQuery('body.single-product').length)
        cartLink = link;
      cartString = '            <div class="visidea__product-add-to-cart"><form method="post" action="' + cartLink +'"><input type="hidden" name="quantity" value="1"><input type="hidden" name="add-to-cart" value="' + product_id + '">' +
      '                <input type="submit" class=" btn btn-primary" role="button" value="Add to cart">' +
      '            </form></div>';    
    }

    return  [
      '    <div class="visidea__product">',
      '        <a href="' + link +'"><img src="' + image +'" class="visidea__product-image" alt="" /></a>',
      '        <div class="visidea__product-caption">',
      '            <p class="visidea__product-heading"><a href="' + link +'">' + title + '</a></p>',
      '            <p class="visidea__product-brand"><a href="' + link +'">' + brand + '</a></p>',
      priceString,
      cartString,
      // '            <a href="' + link +'" class="visidea__product-link btn btn-primary" role="button">See Details</a></p>',
      '        </div>',
      '    </div>',
      ].join('');
  }


  /**
   * Main method to show recommendations.
   * Waits for the configuration of the recommender to be loaded, and then
   * calls 'Visidea.Api.recommend' in order to get a number of recommendations;
   * then, it renders them.
   */
  async recommend(page) {

    var self = this;

    // Wait for 'visidea.conf' to exist
    while (!this.visidea.conf || !this.visidea.conf.currency){
      //console.log('wait');
      await new Promise(r => setTimeout(r,10))
    }

    var visidea_conf = [];
    if (page === 'home')
        visidea_conf = this.visidea.conf.home;
    if (page === 'product')
        visidea_conf = this.visidea.conf.product;
    if (page === 'cart')
        visidea_conf = this.visidea.conf.cart;

    var displayRecommendations = function(res, elem) {

      if (res[0] !== undefined) {
        var recomms_rows = res.map(vals => self.renderProduct(elem, vals['item_id'], vals['name'], vals['brand_name'],
                                          vals['url'], vals['images'][0], vals['price'], vals['market_price']));
        var html = '<div class="visidea">';
        if (elem.title != '')
            html += '<h2>'+elem.title+'</h2>';
        html += '<div class="visidea-slideshow">';
        html += recomms_rows.join('')+'</div></div>';
        if (elem.show_after == '') {
          var append_elem = '';
          if (jQuery('#main').length)
            append_elem = '#main';
          else if (jQuery('#primary').length)
            append_elem = '#primary';
          else if (jQuery('.product').length)
            append_elem = '.product';
          else if (jQuery('main').length)
            append_elem = 'main';
          if (append_elem !== '')
            jQuery(append_elem).append(html);
          else
            console.log('Visidea: cant find element to append, skipping display recommendations!');
        }
        else
          jQuery(elem.show_after).after(html);

        let slides_xs = 2;
        if (elem.slides_xs > 0)
          slides_xs = elem.slides_xs;
        let slides_sm = 4;
        if (elem.slides_sm > 0)
          slides_sm = elem.slides_sm;
        let slides_md = 6;
        if (elem.slides_md > 0)
          slides_md = elem.slides_md;
        let slides_lg = 6;
        if (elem.slides_lg > 0)
          slides_lg = elem.slides_lg;
        let rows = 1;
        if (elem.rows > 0)
          rows = elem.rows;
        let dots = true;
        if (elem.dots !== undefined)
          dots = elem.dots;
        let arrows = true;
        if (elem.arrows !== undefined)
          arrows = elem.arrows;

        jQuery('.visidea-slideshow').each(function(){
          if (!jQuery(this).hasClass('slick-initialized')) {
            var slickIndividual = jQuery(this);
            slickIndividual.slick({
              // lazyLoad: 'ondemand',
              slidesToShow: slides_lg,
              slidesToScroll: 1,
              dots: dots,
              arrows: arrows,
              rows: rows,
              focusOnSelect: true,
              infinite: true,
              responsive: [
                  {
                    breakpoint: 1240,
                    settings: {
                      slidesToShow: slides_md
                    }
                  },
                  {
                    breakpoint: 1024,
                    settings: {
                      arrows: false,
                      slidesToShow: slides_sm
                    }
                  },
                  {
                    breakpoint: 480,
                    settings: {
                      arrows: false,
                      slidesToShow: slides_xs
                    }
                  }
              ]
            });
          }
        });
      }
    }

    if (this.user_id == 0) {
      var visidea_user_id = localStorage.getItem('visidea_user_id');
      if (!visidea_user_id) {
          visidea_user_id = this.visidea.uuidv4();
          localStorage.setItem('visidea_user_id', visidea_user_id);
      }
      this.user_id = visidea_user_id;
    }

    if (jQuery().slick === undefined) {
      const script =  document.currentScript || document.querySelector('script[src*="visidea-client.js"]');
      var myScript = document.createElement('script');
      document.getElementById("main").appendChild(myScript);
      myScript.src = script.src.slice(0,script.src.lastIndexOf('/'))+'/slick.min.js';
      myScript.addEventListener('load', () => {
        for (var elem of visidea_conf) {
          if (elem.show) {
            this.visidea.recommend(elem.algo, this.user_id, this.product_id, {}, elem.n, displayRecommendations, elem);
          }
        }
      });
    } else {
      for (var elem of visidea_conf) {
        if (elem.show) {
          this.visidea.recommend(elem.algo, this.user_id, this.product_id, {}, elem.n, displayRecommendations, elem);
        }
      }
    }

  }

  /**
  * Merge the current user into the customer_id
  * @param {string}   customer_id   id of the customer
  */
  async mergeUser(customer_id){

    // Wait for 'visidea.conf' to exist
    while (!this.visidea.conf){
      //console.log('wait');
      await new Promise(r => setTimeout(r,10))
    }

    if (this.user_id == 0) {
      var visidea_user_id = localStorage.getItem('visidea_user_id');
      if (!visidea_user_id) {
          visidea_user_id = this.visidea.uuidv4();
          localStorage.setItem('visidea_user_id', visidea_user_id);
      }
      this.user_id = visidea_user_id;
    }

    if (this.user_id !== customer_id)
      this.visidea.merge_users(this.user_id, customer_id);

  }

  /**
   *  Method to wait for a resource to be loaded. Won't work with
   *  references to `this` objects.
   * @param {string}    name      resource to wait for
   * @param {function}  callback  function to be called when the resource is loaded
   */
  whenExists(name, callback) {
        var interval = 10; // ms
        window.setTimeout(function() {
            if (eval(name) != undefined) {
                callback();
            } else {
                window.setTimeout(arguments.callee, interval);
            }
        }, interval);
  }

}
