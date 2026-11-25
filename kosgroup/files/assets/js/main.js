document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.video-wrapper').forEach(wrapper => {
        wrapper.addEventListener('click', function() {
            const videoId = this.dataset.youtubeId;
            
            // Creo l'iframe dinamicamente
            const iframe = document.createElement('iframe');
            
            iframe.setAttribute('src', `https://www.youtube.com/embed/${videoId}?autoplay=1`);
            iframe.setAttribute('frameborder', '0');
            iframe.setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture');
            iframe.setAttribute('allowfullscreen', '');
            
            // Aggiungo le classi necessarie all'iframe
            iframe.style.position = 'absolute';
            iframe.style.top = '0';
            iframe.style.left = '0';
            iframe.style.width = '100%';
            iframe.style.height = '100%';
            
            this.appendChild(iframe);
            this.classList.add('active');
        });
    });
});

document.addEventListener('DOMContentLoaded', function () {
    // Usa jQuery per tutto il blocco
    if (jQuery('.toggle-header').length > 0) {
        const $toggleHeader = jQuery('.toggle-header');
        const $toggleContent = jQuery('.toggle-content');
        
        // Verifica che entrambi gli elementi esistano con jQuery
        if ($toggleHeader.length && $toggleContent.length) {
            $toggleContent.hide();
            $toggleHeader.on('click', function () {
                const isContentVisible = $toggleContent.is(':visible');
                $toggleContent.toggle(!isContentVisible);
                $toggleHeader.toggleClass('active', !isContentVisible);
            });
        }
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const button = document.getElementById('backToTop');
    
    // Verifica che il bottone esista prima di procedere
    if (button) {
        // Mostra/nascondi il bottone in base allo scroll
        window.onscroll = function() {
            if (document.body.scrollTop > 200 || document.documentElement.scrollTop > 200) {
                button.style.display = "block";
            } else {
                button.style.display = "none";
            }
        };
        
        // Gestisci il click sul bottone
        button.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }
});

(function ($) {
    "use strict";

    const itemsPerPage = 9;
    let currentPage = 1;
    let paginatedResults = [];

    jQuery(document).ready(function ($) {
        if ($('.risultati').length > 0) {
            loadData();
            populateFilters();
            updateFilterCounts();
            addFilterListeners();
            applyURLFilters();
            addMobileSearchListener();
            updateStructureInfo();
        }

        // Apertura dei filtri su mobile
        $('.apri-filtri-mobile img').on('click', function () {
            $('#strutture-filtri').addClass('active');
        });

        // Chiusura dei filtri su mobile (per il tasto "Torna alla ricerca" e altri elementi)
        $('.chiudi-filtri-mobile, .filtri-torna, .filtri-mobile-btns .btn-primary').on('click', function (e) {
            e.preventDefault();
            $('#strutture-filtri').removeClass('active');
        });

        // Ripristina filtri
        $('.filtri-mobile-btns .btn-secondary').on('click', function (e) {
            e.preventDefault();
            
            // Deseleziona tutte le opzioni nei checkbox di ciascun filtro
            $('#region-filters input[type="checkbox"], #structures-filters input[type="checkbox"], #services-filters input[type="checkbox"]').prop('checked', false);

            // Svuota eventuali campi di input per la ricerca
            $('#mobile-search').val('');
            
            // Aggiorna i risultati e i contatori dopo il reset
            filterResults();  // Chiama la funzione esistente per filtrare i risultati

            // Opzionale: aggiorna i contatori per riflettere il reset
            updateFilterCounts();
        });

        function loadData() {
            filterResults();
        }

        function populateFilters() {
            const regionsSet = new Set();
            const structuresSet = new Set();
            const servicesSet = new Set();
            const doctorsSet = new Set();

            strutture.forEach(function (item) {
                if (item.regione) {
                    regionsSet.add(item.regione);
                }
                if (item.struttura && Array.isArray(item.struttura)) {
                    item.struttura.forEach(function (structure) {
                        structuresSet.add(structure);
                    });
                }
                if (item.servizi && Array.isArray(item.servizi)) {
                    item.servizi.forEach(function (service) {
                        servicesSet.add(service);
                    });
                }
                if (item.medici && Array.isArray(item.medici)) {
                    item.medici.forEach(function (doctor) {
                        doctorsSet.add(doctor);
                    });
                }
            });

            populateFilterOptions('#region-filters', Array.from(regionsSet).sort());
            populateFilterOptions('#structures-filters', Array.from(structuresSet).sort());
            populateFilterOptions('#services-filters', Array.from(servicesSet).sort());
            populateFilterOptions('#doctor-filters', Array.from(doctorsSet).sort());
        }

        function populateFilterOptions(filterContainerSelector, optionsArray) {
            const container = $(filterContainerSelector);
            container.empty();

            optionsArray.forEach(function (option) {
                const optionElement = `
                    <div class="single-item position-relative">
                        <label>
                            <input type="checkbox" value="${option}">
                            <span class="checkbox-label">${option}</span>
                        </label>
                    </div>
                `;
                container.append(optionElement);
            });
        }

        function applyURLFilters() {
            const urlParams = new URLSearchParams(window.location.search);

            if (urlParams.has('region')) {
                const regions = urlParams.get('region').split(',');
                regions.forEach(region => {
                    $(`#region-filters input[value="${region}"]`).prop('checked', true);
                });
                updateDependentCounters(regions);
            }

            if (urlParams.has('structure')) {
                const structures = urlParams.get('structure').split(',');
                structures.forEach(structure => {
                    $(`#structures-filters input[value="${structure}"]`).prop('checked', true);
                });
            }

            if (urlParams.has('service')) {
                const services = urlParams.get('service').split(',');
                services.forEach(service => {
                    $(`#services-filters input[value="${service}"]`).prop('checked', true);
                });
            }

            filterResults();
        }

        function updateStructureInfo() {
            // Calcola i totali complessivi, indipendentemente dai filtri
            const totalStructures = strutture.length;
            const uniqueRegions = new Set(strutture.map(item => item.regione)).size;

            // Aggiorna il testo mantenendo i totali complessivi
            $('#info-strutture').text(`${totalStructures} strutture in ${uniqueRegions} regioni. Scoprile.`);
        }

        function addMobileSearchListener() {
            $('#mobile-search').on('input', function () {
                filterResults();
            });
        }

        function filterResults() {
            const searchText = $('#mobile-search').val().toLowerCase();
            const selectedRegions = getSelectedFilters('region-filters');
            const selectedStructures = getSelectedFilters('structures-filters');
            const selectedServices = getSelectedFilters('services-filters');
            const selectedDoctors = getSelectedFilters('doctor-filters');

            paginatedResults = strutture.filter(function (item) {
                const matchRegion = selectedRegions.length === 0 || selectedRegions.includes(item.regione);
                const matchStructure = selectedStructures.length === 0 || item.struttura.some(structure => selectedStructures.includes(structure));
                const matchService = selectedServices.length === 0 || item.servizi.some(service => selectedServices.includes(service));
                const matchDoctor = selectedDoctors.length === 0 || item.medici.some(doctor => selectedDoctors.includes(doctor));
                const matchName = item.nome.toLowerCase().includes(searchText);

                return matchRegion && matchStructure && matchService && matchDoctor && matchName;
            });

            currentPage = 1;
            displayPaginatedResults();
            
            // Aggiorna i contatori quando cambiano i risultati
            updateDependentCounters(selectedRegions);
        }

        function updateDependentCounters(selectedRegions) {
            let filteredStrutture = strutture;
            if (selectedRegions && selectedRegions.length > 0) {
                filteredStrutture = strutture.filter(item => selectedRegions.includes(item.regione));
            }

            const structureCount = new Map();
            const serviceCount = new Map();

            // Inizializza tutti i contatori a 0
            $('#structures-filters input[type="checkbox"]').each(function() {
                structureCount.set($(this).val(), 0);
            });
            $('#services-filters input[type="checkbox"]').each(function() {
                serviceCount.set($(this).val(), 0);
            });

            // Calcola i nuovi conteggi
            filteredStrutture.forEach(item => {
                if (item.struttura && Array.isArray(item.struttura)) {
                    item.struttura.forEach(structure => {
                        structureCount.set(structure, (structureCount.get(structure) || 0) + 1);
                    });
                }
                if (item.servizi && Array.isArray(item.servizi)) {
                    item.servizi.forEach(service => {
                        serviceCount.set(service, (serviceCount.get(service) || 0) + 1);
                    });
                }
            });

            // Aggiorna le etichette e lo stato dei checkbox
            updateFilterOptionCounts('#structures-filters', structureCount);
            updateFilterOptionCounts('#services-filters', serviceCount);
        }

        function updateFilterOptionCounts(filterContainerSelector, countMap) {
            $(filterContainerSelector).find('.single-item').each(function() {
                const checkbox = $(this).find('input[type="checkbox"]');
                const label = $(this).find('.checkbox-label');
                const value = checkbox.val();
                const count = countMap.get(value) || 0;
                
                // Aggiorna il testo della label
                label.text(`${value} (${count})`);
                
                // Gestisce lo stato disabled
                checkbox.prop('disabled', count === 0);
                $(this).toggleClass('disabled', count === 0);
            });
        }

        function updateFilterCounts() {
            const regionCount = new Map();
            const structureCount = new Map();
            const serviceCount = new Map();
            const doctorCount = new Map();

            strutture.forEach(item => {
                if (item.regione) {
                    regionCount.set(item.regione, (regionCount.get(item.regione) || 0) + 1);
                }
                if (item.struttura) {
                    item.struttura.forEach(structure => {
                        structureCount.set(structure, (structureCount.get(structure) || 0) + 1);
                    });
                }
                if (item.servizi) {
                    item.servizi.forEach(service => {
                        serviceCount.set(service, (serviceCount.get(service) || 0) + 1);
                    });
                }
                if (item.medici) {
                    item.medici.forEach(doctor => {
                        doctorCount.set(doctor, (doctorCount.get(doctor) || 0) + 1);
                    });
                }
            });

            updateFilterOptionCounts('#region-filters', regionCount);
            updateFilterOptionCounts('#structures-filters', structureCount);
            updateFilterOptionCounts('#services-filters', serviceCount);
            updateFilterOptionCounts('#doctor-filters', doctorCount);
        }

        function addFilterListeners() {
            $('#region-filters').on('change', 'input[type="checkbox"]', function() {
                const selectedRegions = getSelectedFilters('region-filters');
                updateDependentCounters(selectedRegions);
                filterResults();
            });

            $('#structures-filters, #services-filters, #doctor-filters').on('change', 'input[type="checkbox"]', filterResults);

            $('.region__search input').on('input', function() {
                const containerId = $(this).closest('[id]').attr('id');
                const filterContainerId = {
                    'region': '#region-filters',
                    'struttura': '#structures-filters',
                    'service': '#services-filters',
                    'doctor': '#doctor-filters'
                }[containerId];
                
                if (filterContainerId) {
                    filterSelectOptions(this, filterContainerId);
                }
            });
        }

        function filterSelectOptions(searchInput, filterContainer) {
            const searchText = $(searchInput).val().toLowerCase();
            $(filterContainer).find('.single-item').each(function () {
                const itemText = $(this).find('.checkbox-label').text().toLowerCase();
                $(this).toggle(itemText.includes(searchText));
            });
        }

        function getSelectedFilters(filterId) {
            const selectedFilters = [];
            $('#' + filterId + ' input[type="checkbox"]:checked').each(function () {
                selectedFilters.push($(this).val());
            });
            return selectedFilters;
        }

        function displayPaginatedResults() {
            const totalPages = Math.ceil(paginatedResults.length / itemsPerPage);
            const startIndex = (currentPage - 1) * itemsPerPage;
            const endIndex = startIndex + itemsPerPage;
            const paginatedItems = paginatedResults.slice(startIndex, endIndex);

            displayResults(paginatedItems);
            createPagination(totalPages);
        }

        function createPagination(totalPages) {
            const paginationContainer = $('.service__pagination .pagination');
            paginationContainer.empty();

            for (let i = 1; i <= totalPages; i++) {
                const pageItem = $(`<li class="page-item ${i === currentPage ? 'active' : ''}"><a class="page-link" href="#">${i}</a></li>`);
                pageItem.on('click', function (e) {
                    e.preventDefault();
                    currentPage = i;
                    displayPaginatedResults();
                });
                paginationContainer.append(pageItem);
            }
        }

        function displayResults(results) {
            const container = $('#results-container');
            container.empty();

            results.forEach(function (item) {
                const card = `
                    <div class="col-12 col-md-6 col-lg-4 struttura-card">
                        <div class="struttura-img">
                            <img src="${item.immagine}" alt="struttura ${item.nome}">
                        </div>
                        <div class="struttura-content mb-3 mb-md-4">
                            <h4>${item.nome}</h4>
                            <p>${item.indirizzo}</p>
                        </div>
                        <!-- <div class="struttura-features-container flex-wrap">
                            <div class="struttura-features d-flex justify-content-end flex-wrap">
                                ${item.servizi.slice(0, 2).map(service => `<span class="tag">${service.trim()}</span>`).join('')}
                            </div>
                            <div class="struttura-features-nascoste collapse" id="moretags${item.riferimento}">
                                ${item.servizi.slice(2).map(service => `<span class="tag">${service.trim()}</span>`).join('')}
                            </div>
                            <a data-bs-toggle="collapse" href="#moretags${item.riferimento}" role="button" aria-expanded="false" aria-controls="moretags${item.riferimento}" class="struttura-features-more text-white d-flex align-items-center justify-content-center rounded-0 p-0">
                                <span class="plus-icon"><i class="fal fa-plus"></i></span>
                                <span class="minus-icon"><i class="fal fa-minus"></i></span>
                            </a>
                        </div> -->
                    </div>
                `;
                container.append(card);
            });
        }



        //------------ Offcanvas menu -------------

        $('.open_mobile_menu').on('click', function() {
            //console.log('apro menu');
            $('body').addClass('overflow-hidden');
            $(this).addClass('d-none');
            $('.close_mobile_menu').removeClass('d-none');
            $('#menu-mobile, .overlay').addClass('active');
            $('#menu-mobile').animate({left:'0'},500);
            $('.overlay').animate({left:'0'},200);
        });
        $('.close_mobile_menu, .overlay').on('click', function() {
            //console.log('chiudo menu');
            $('body').removeClass('overflow-hidden');
            $(this).addClass('d-none');
            $('.open_mobile_menu').removeClass('d-none');
            $('#menu-mobile, .overlay').removeClass('active');
            $('#menu-mobile').animate({left:'-100%'},500);
            $('.overlay').animate({left:'-100%'},200);
        });

        

        $('#menu-mobile nav > ul > li').on('click', function(){
            $(this).addClass('menu-item-opened');
            $(this).find('> .sub-menu').animate({left:'0'},500);
        });
        $('#menu-mobile [data-level="1"] > ul > li').on('click', function(){
            $(this).addClass('menu-item-opened');            
            $(this).find('> .sub-menu').animate({left:'0'},500);
        }); 

        $('#menu-mobile .sub-menu-title').on('click', function(event){
            event.stopPropagation();
            $(this).parent('.sub-menu').animate({left:'100%'},500);
            setTimeout(function(){
                $(this).closest('li').removeClass('menu-item-opened');
            },500);            
        });

        //------------ Offcanvas menu -------------






        /*https://github.com/michalsnik/aos*/
        // You can also pass an optional settings object, below listed default settings 
        AOS.init({
            // Global settings:
            disable: false, // accepts following values: 'phone', 'tablet', 'mobile', boolean, expression or function
            startEvent: 'DOMContentLoaded', // name of the event dispatched on the document, that AOS should initialize on
            initClassName: 'aos-init', // class applied after initialization
            animatedClassName: 'aos-animate', // class applied on animation
            useClassNames: false, // if true, will add content of `data-aos` as classes on scroll
            disableMutationObserver: false, // disables automatic mutations' detections (advanced)
            debounceDelay: 50, // the delay on debounce used while resizing window (advanced)
            throttleDelay: 99, // the delay on throttle used while scrolling the page (advanced)

            // Settings that can be overridden on per-element basis, by `data-aos-*` attributes:
            offset: 120, // offset (in px) from the original trigger point
            delay: 100, // values from 0 to 3000, with step 50ms
            duration: 700, // values from 0 to 3000, with step 50ms
            easing: 'ease', // default easing for AOS animations
            once: true, // whether animation should happen only once - while scrolling down
            mirror: false, // whether elements should animate out while scrolling past them
            anchorPlacement: 'top-bottom', // defines which position of the element regarding to window should trigger the animation
        });


        //larghezza finestra per responsive:
        var winW = $('header').innerWidth();

        //se ultima sezione ha bg-dark, azzurro o grigio, footer non ha margin-top
        if( $('.main__content').children().last().hasClass('bg-dark') || 
            $('.main__content').children().last().hasClass('bg-grigio') || 
            $('.main__content').children().last().hasClass('bg-azzuro') || 
            $('.main__content').children().last().hasClass('bg-pattern') || 
            $('.main__content').children().last().hasClass('footer-m-0')  ){            
            $('footer').addClass('mt-0');
        }


        // servizi carosello
        if( $('#blocco-servizi-carosello').length > 0 ){
            $('#blocco-servizi-carosello .servizio-tassello').addClass('swiper-slide');
                
            const serviziCarosello = new Swiper('#blocco-servizi-carosello', {
                speed: 400,
                freeMode: false,
                rewind: true,
                grabCursor: true,
                navigation: {
                    nextEl: '.swiper-button-next',
                    prevEl: '.swiper-button-prev',
                },
                on: {
                    lock: function(){
                       $(this.el).addClass('inattivo');
                    },
                    unlock: function(){
                       $(this.el).removeClass('inattivo');
                    },
                    /*slideChange: function(){
                        //console.log(this);
                        //console.log(this.el);
                    }*/
                },
                slidesPerView: 1.13,
                //slidesOffsetAfter: 31, //container padding + 15px
                spaceBetween: 16,
                breakpoints: {
                    // when window width is >= 768
                    768: {
                       slidesPerView: 2.23,
                       //slidesOffsetAfter: 53,
                       spaceBetween: 16
                    },
                    // when window width is >= 992
                    992: {
                       slidesPerView: 2.68,
                       //slidesOffsetAfter: 53,
                       spaceBetween: 16 
                    },
                    // when window width is >= 1200
                    1200: {
                       slidesPerView: 3.12,
                       //slidesOffsetAfter: 79,
                       spaceBetween: 24 
                    },
                    // when window width is >= 1300
                    1300: {
                       slidesPerView: 4,
                       //slidesOffsetAfter: 79,
                       spaceBetween: 32
                    }
                }
            }); //Swiper
        }



          // ===== INIZIO carosello progetti =====
          if( $('#blocco-progetti-carosello') ){

              var bloccoProgetti = $('#blocco-progetti-carosello');
              var numProgetti = bloccoProgetti.children().length;

              if (numProgetti == 1) {
                  bloccoProgetti.addClass("num-prog-1");
              } else if (numProgetti == 2) {
                  bloccoProgetti.addClass("num-prog-2");
              } else if (numProgetti >= 3) {
                  bloccoProgetti.addClass("num-prog-3");
              }

              var attivaCarosello = false;

              if(winW < 768 ){
                  attivaCarosello = true;          // su mobile sempre carosello
              } else {
                  if( numProgetti > 3 ){
                      attivaCarosello = true;      // su desktop solo da 4 progetti in su
                  }
              }

              if(attivaCarosello == true){
                  bloccoProgetti.addClass('swiper');
                  bloccoProgetti.find('.progetto-singolo').wrapAll('<div class="col swiper-wrapper"></div>');
                  bloccoProgetti.find('.progetto-singolo').addClass('swiper-slide');
                  var controlsHtml =
                      '<div class="swiper-controls">' +
                      '<div class="swiper-button-prev"><img src="/assets/img/angle_left.svg" alt=""></div>' +
                      '<div class="swiper-button-next"><img src="/assets/img/angle_right.svg" alt=""></div>' +
                      '</div>';
                  bloccoProgetti.find('.swiper-wrapper').after(controlsHtml);

                  const progettiCarosello = new Swiper('#blocco-progetti-carosello', {
                      speed: 400,
                      freeMode: false,
                      rewind: true,
                      grabCursor: true,
                      navigation: {
                          nextEl: '.swiper-button-next',
                          prevEl: '.swiper-button-prev',
                      },
                      on: {
                          lock: function(){
                             $(this.el).addClass('inattivo');
                          },
                          unlock: function(){
                             $(this.el).removeClass('inattivo');
                          },
                      },
                      slidesPerView: 1,
                      spaceBetween: 36,
                      breakpoints: {
                          // >= 768px (tablet)
                          768: {
                             slidesPerView: 2
                          },
                          // >= 1200px (desktop)
                          1200: {
                             slidesPerView: 3
                          }
                      }
                  }); //Swiper
              } // attiva carosello?

          } //esistono progetti
          // ===== FINE carosello progetti =====






        // articoli carosello
        if( $('#blocco-articoli-carosello').length > 0 ){
            $('#blocco-articoli-carosello .articolo-tassello').addClass('swiper-slide');
                
            const articoliCarosello = new Swiper('#blocco-articoli-carosello', {
                speed: 400,
                freeMode: false,
                rewind: true,
                grabCursor: true,
                navigation: {
                    nextEl: '.swiper-button-next',
                    prevEl: '.swiper-button-prev',
                },
                on: {
                    lock: function(){
                       $(this.el).addClass('inattivo');
                    },
                    unlock: function(){
                       $(this.el).removeClass('inattivo');
                    },
                    /*slideChange: function(){
                        //console.log(this);
                        //console.log(this.el);
                    }*/
                },
                slidesPerView: 1,
                spaceBetween: 36,
                //slidesOffsetAfter: 31, //container padding + 15px
                breakpoints: {
                    // when window width is >= 992
                    992: {
                       slidesPerView: 2,
                       //slidesOffsetAfter: 53,

                    }
                }
            }); //Swiper
        }



        
        // carosello con news
        if( $('.box-news.box-news-carousel').length > 0 && winW > 991){

            $('.box-news.box-news-carousel').each(function(i){
                $(this).addClass('swiper swiperNews'+i );
                $(this).find('.news-singola').wrapAll('<div class="col swiper-wrapper"></div>');
                $(this).find('.news-singola').addClass('swiper-slide');
                $(this).find('.swiper-wrapper').after('<div class="swiper-controls"><div class="swiper-button-prev"><img src="assets/img/angle_left.svg" alt=""></div><div class="swiper-button-next"><img src="assets/img/angle_right.svg" alt=""></div></div>');

                //console.log( $(this) );
                const caroselloNews = new Swiper( this, {
                    speed: 400,
                    freeMode: false,
                    rewind: true,
                    grabCursor: true,
                    navigation: {
                        nextEl: '.swiper-button-next',
                        prevEl: '.swiper-button-prev',
                    },
                    on: {
                        lock: function(){
                           $(this.el).addClass('inattivo');
                        },
                        unlock: function(){
                           $(this.el).removeClass('inattivo');
                        }
                    },
                    slidesPerView: 2,
                    spaceBetween: 36,
                    breakpoints: {
                        // when window width is >= 992
                        1200: {
                           slidesPerView: 3,
                           //slidesOffsetAfter: 53,

                        }
                    }
                }); //Swiper

            }); //each            
        }

        // carosello con recensioni
            $('.box-recens-carousel').each(function(i){
                $(this).addClass('swiper swiperRec'+i );
                $(this).find('.recens-singola').wrapAll('<div class="col swiper-wrapper"></div>');
                $(this).find('.recens-singola').addClass('swiper-slide');
                $(this).find('.swiper-wrapper').after('<div class="swiper-controls"><div class="swiper-button-prev"><img src="assets/img/angle_left.svg" alt=""></div><div class="swiper-button-next"><img src="assets/img/angle_right.svg" alt=""></div></div>');

                //console.log( $(this) );
                const caroselloNews = new Swiper( this, {
                    speed: 400,
                    freeMode: false,
                    rewind: true,
                    grabCursor: true,
                    navigation: {
                        nextEl: '.swiper-button-next',
                        prevEl: '.swiper-button-prev',
                    },
                    on: {
                        lock: function(){
                           $(this.el).addClass('inattivo');
                        },
                        unlock: function(){
                           $(this.el).removeClass('inattivo');
                        }
                    },
                    slidesPerView: 1,
                    spaceBetween: 36,
                    breakpoints: {
                        // when window width is >= 992
                        1200: {
                           slidesPerView: 3,
                           //slidesOffsetAfter: 53,

                        }
                    }
                }); //Swiper

            }); //each  


        // carosello con immagini solo da mobile
            $('.imgs-carosello-mobile').each(function(i){
                if(winW < 992){

                    $(this).addClass('swiper swiperImgs'+i );
                    $(this).find('.img-crsl').wrapAll('<div class="col swiper-wrapper"></div>');
                    $(this).find('.img-crsl').addClass('swiper-slide');
                    $(this).find('.swiper-wrapper').after('<div class="swiper-controls"><div class="swiper-button-prev"><img src="assets/img/angle_left.svg" alt=""></div><div class="swiper-button-next"><img src="assets/img/angle_right.svg" alt=""></div></div>');

                    //console.log( $(this) );
                    const caroselloImgs = new Swiper( this, {
                        speed: 400,
                        freeMode: false,
                        rewind: true,
                        grabCursor: true,
                        navigation: {
                            nextEl: '.swiper-button-next',
                            prevEl: '.swiper-button-prev',
                        },
                        on: {
                            lock: function(){
                               $(this.el).addClass('inattivo');
                            },
                            unlock: function(){
                               $(this.el).removeClass('inattivo');
                            }
                        },
                        slidesPerView: 1,
                        spaceBetween: 36,
                        /*breakpoints: {
                            // when window width is >= 992
                            1200: {
                               slidesPerView: 2,
                               //slidesOffsetAfter: 53,

                            }
                        },*/
                        autoHeight: true
                    }); //Swiper
                }

            }); //each          



        if(winW < 992){
            $(".documento-nome").contents().filter(function(){
                  return this.nodeType == 3 && $.trim($(this).text()) != '';
            }).wrap('<span class="dc-nome">'); //wrappo ogni nodo di testo in un <li>
        }



        //sticky sidebars
        if ($('.sticky-sidebar').length > 0 && winW >= 992) {
            $('.sticky-sidebar').each(function(i) {
                $(this).addClass('sidebar' + i);
                var sidebar = new StickySidebar('.sticky-sidebar.sidebar' + i, {
                    topSpacing: 20,
                    bottomSpacing: 20,
                    innerWrapperSelector: '.sidebar__inner'
                });
            });
        }

        //da mobile, per la sidebar INLINE i pulsanti diventano fissi in basso
        //Presupposto: è solo una per pagina (altrimenti diventa complicato gestire più fixed da mobile)
        if ( $('.sidebar-inline').length > 0 && winW < 992 ) {
            var hBtnsContainer = $('.sidebar-inline .sidebar-btns').innerHeight();
            $('.sidebar-inline .sidebar-btns').height(hBtnsContainer);
            $('.sidebar-inline .sidebar-btns').attr('data-height', hBtnsContainer);
        }
        $(window).scroll(function(){
            if ( $('.sidebar-inline').length > 0 && winW < 992 ) {
                var sidebarInlineBtnsPos =  $('.sidebar-inline .sidebar-btns').offset().top;
                var sidebarInlineBtnsHeight =  parseInt($('.sidebar-inline .sidebar-btns').attr('data-height'));
                var sidebarStartFix = sidebarInlineBtnsPos + sidebarInlineBtnsHeight;
                
                if ($(window).scrollTop() >= sidebarStartFix) {
                    if( $('.sidebar-btns-fixed').length < 1 ){
                        $('.sidebar-inline .sidebar-btns').wrapInner('<div class="sidebar-btns-fixed"></div>');
                        $('body').css('padding-bottom', 72);
                    }
                } else {
                    if( $('.sidebar-btns-fixed').length > 0 ){
                        $('.sidebar-inline .sidebar-btns-fixed > .btn').unwrap();
                        $('body').css('padding-bottom', 0);
                    }
                }
            }            
        });

        // KOS 25/11/2026: i link alle sezioni della pagina arrivano come "#id" oppure "/percorso-della-pagina#id"
        // (plugin LinkFrammenti, per il <base href> i crawler leggevano "#id" come link alla home): si scorre in
        // entrambi i casi; i link ad altre pagine con frammento restano link normali.
        $('.sidebar-content a[href*="#"], p a[href*="#"], #intro-pagina-content a, #intro-pagina-foto a').on('click', function(event) {
            var href = $(this).attr('href');
            var id = '';
            if (href && href.charAt(0) === '#') {
                id = href.slice(1);
            } else if (href && this.hash && this.hash.length > 1 && this.hostname === window.location.hostname
                    && this.pathname.replace(/\/+$/, '') === window.location.pathname.replace(/\/+$/, '')) {
                id = this.hash.slice(1);
            }

            // Esegui solo se l'elemento è un'ancora e non un link normale
            if (href && (href.charAt(0) === '#' || id)) {
                event.preventDefault();
                event.stopPropagation();
                event.stopImmediatePropagation();

                var target = id ? $(document.getElementById(decodeURIComponent(id))) : $();
                if (target.length) {
                    $('html, body').stop().animate({scrollTop: target.offset().top - 80}, 500); 
                }
            }
        });


    }); //---document-ready-----


    jQuery(window).load(function($) {
        var winW = $('header').innerWidth();


//funzione per controllare la visibilità e attivare i contatori
function checkCounters() {
    $('.counter-txt').each(function() {
        // Calcolo la posizione dell'elemento
        var counterPos = $(this).offset().top;
        
        // Calcolo la posizione attuale dello scroll
        var scrollatura = $(window).scrollTop();
        
        // Calcolo l'altezza della finestra
        var windowHeight = $(window).height();
        
        // Verifica se l'elemento è visibile nella finestra
        if (scrollatura + windowHeight > counterPos) {
            // Controllo se l'animazione è già stata avviata
            if (!$(this).hasClass('counted')) {
                // Aggiungo una classe per evitare di riavviare l'animazione
                $(this).addClass('counted');
                
                // Seleziono i contatori all'interno di questo elemento
                const counters = $(this).find('.counter-to');
                // Non utilizziamo più la costante speed perché abbiamo un calcolo dinamico dell'incremento
                
                counters.each(function() {
                    const counter = this;
                    const animate = () => {
                        const numFinale = +counter.getAttribute('num-finale');
                        const numCorrente = +counter.getAttribute('num-corrente') || 0;
                        
                        // Calcolo un incremento basato sul valore finale
                        // Usa un incremento maggiore per numeri più grandi
                        let incremento;
                        if (numFinale > 100000) {
                            incremento = numFinale / 150; // Incremento più grande per numeri molto alti
                        } else if (numFinale > 10000) {
                            incremento = numFinale / 100; // Incremento medio per numeri alti
                        } else if (numFinale > 1000) {
                            incremento = numFinale / 60; // Incremento moderato per numeri medi
                        } else {
                            incremento = numFinale / 100; // Incremento standard per numeri piccoli
                        }
                        
                        // Aggiorno contatore numero su attributo
                        if (numCorrente < numFinale) {
                            const numIntero = Math.ceil(numCorrente + incremento);
                            // Assicuriamoci di non superare il valore finale
                            const nuovoValore = numIntero > numFinale ? numFinale : numIntero;
                            counter.setAttribute('num-corrente', nuovoValore);
                            setTimeout(animate, 20); // Aumentiamo anche l'intervallo per renderlo più fluido
                        } else {
                            counter.setAttribute('num-corrente', numFinale);
                            counter.innerText = numFinale;
                        }
                        
                        // Aggiorno contatore stringa con punto in innerText div
                        const numCorrenteDef = +counter.getAttribute('num-corrente');
                        if (numCorrenteDef > 999) {
                            const numCorrenteStr = numCorrenteDef.toString();
                            const numPuntato = numCorrenteStr.slice(0, -3) + '.' + numCorrenteStr.slice(-3);
                            counter.innerText = numPuntato;
                        } else {
                            counter.innerText = numCorrenteDef;
                        }
                    };
                    
                    animate();
                });
            }
        }
    });
}

// Esegui al caricamento della pagina
jQuery(document).ready(function() {
    checkCounters();
});

// Esegui durante lo scroll
jQuery(window).scroll(function() {
    checkCounters();
});

// Esegui al caricamento della pagina
jQuery(document).ready(function() {
    checkCounters();
});

// Esegui durante lo scroll
jQuery(window).scroll(function() {
    checkCounters();
});



        //aggiungiamo padding a .padding-auto-sx
        function setPadding() {

            // larghezza della finestra
            var winW = $('header').innerWidth();
            //console.log('Larghezza della finestra:', winW);

            if( winW > 991 ){
                // larghezza del container dell'header
                var containerWidth = $('header .container').innerWidth();
                //console.log('Larghezza del container:', containerWidth);

                // calcolo padding 
                var paddingLeft = (winW - containerWidth) / 2;
                //console.log('Padding sinistro calcolato:', paddingLeft);

                // Applica il padding  a .padding-auto-sx
                $('.padding-auto-sx').css('padding-left', paddingLeft + 'px');
                //console.log('Padding sinistro applicato:', $('.padding-auto-sx').css('padding-left'));

                /* usato anche per il menu, e dà alcune proporzioni: */
                $('.sub-menu.padding-auto-sx').each(function(){
                    $(this).css('padding-right', paddingLeft + 'px');
                    var larghMenuLlvArea = $(this).width(); //esclude padding
                    //il menu al primo livello è largo 31%, quindi calcolo questo rapportato alla larghezza
                    var larghMenuLlv1 = (larghMenuLlvArea*31)/100;
                    var larghMenuLlv1ConPaddingSx = larghMenuLlv1 + paddingLeft;
                    if( $(this).find('.sub-menu-divider').length < 1 ){
                        $(this).append('<i class="sub-menu-divider"></i>'); /* style="left:'+larghMenuLlv1ConPaddingSx+'px" */
                    }     
                    $(this).find('.sub-menu-divider').css('left', larghMenuLlv1ConPaddingSx);
                    $(this).find('.sub-menu[data-level="2"]').css({"left": larghMenuLlv1ConPaddingSx, "width": "calc(100% - "+larghMenuLlv1ConPaddingSx+"px)"});          
                });
                
                
            } else {
                $('.padding-auto-sx').removeAttr('style');
            }     

            


        }
        setPadding(); // al window load

        $(window).resize(setPadding);




        //La lightbox parte solo al click su "Vedi tutte"
        if ($('.gallery-struttura').length > 0) {          

            $(document).on('click', '.gallery-struttura .gallery-tutte', function(e) {
                               
                e.preventDefault();

                var strutturaImgs = [];
                $('.gallery-struttura > .gallery-foto').each(function() {
                    var src = $(this).attr('data-pswp-src');
                    var w = $(this).attr('data-pswp-width');
                    var h = $(this).attr('data-pswp-height');
                    var img = {
                        src: src,
                        width: w,
                        height: h
                    }
                    strutturaImgs.push(img);
                });
                ////console.log(strutturaImgs);  

                //const leftArrowSVGString = '<svg aria-hidden="true" class="pswp__icn" viewBox="0 0 100 125" width="100" height="125"><path d="M5,50L50,5l3,3L11,50l42,42l-3,3L5,50z M92,95l3-3L53,50L95,8l-3-3L47,50L92,95z"/></svg>';
                const arrChevron = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M6 3.33341L10 8.00008L6 12.6667" stroke="#2091B0" stroke-linecap="round" stroke-linejoin="round"/></svg>';

                const optionsGallery = {
                    //gallery: ".della__gallery",
                    //children: ".gallery_foto",
                    dataSource: strutturaImgs,
                    bgOpacity: 0.8,
                    clickToCloseNonZoomable: false,
                    loop: false,
                    /*padding: {
                        top: 50,
                        bottom: 120,
                        left: 60,
                        right: 60
                    },*/
                    arrowPrevSVG: arrChevron,
                    arrowNextSVG: arrChevron,
                    pswpModule: PhotoSwipe 
                }; 

                //console.log(optionsGallery);
                if( winW >= 992 ){
                    //console.log('siamo sopra 991');
                    optionsGallery['padding'] = {top: 50, bottom: 130, left: 60, right: 60} ;
                    //optionsGallery.padding = {top: 50, bottom: 120, left: 60, right: 60}
                } else {
                    //console.log('siamo sotto 991');
                    optionsGallery['padding'] = {top: 60, bottom: 160, left: 10, right: 10};
                    //optionsGallery.padding = {top: 50, bottom: 120, left: 10, right: 10}
                }
                //console.log(optionsGallery);

                const galleryStruttura = new PhotoSwipeLightbox(optionsGallery);
                //const galleryStruttura = new PhotoSwipe(optionsGallery);

                // apro ( e quindi inizializzo la lightbox )
                galleryStruttura.loadAndOpen(0);
                //console.log('apro gallery alla prima');


                galleryStruttura.on('init', function(){
                    //console.log('inizializzo');
                    $('body').append('<div class="photoSwipe_innerthumbs"></div>');
                    $('.gallery-struttura .gallery-foto img').clone().appendTo("div.photoSwipe_innerthumbs");
                    //console.log('aggiungo thumbs a init');
                });

                galleryStruttura.on('change', function(){
                    //console.log(galleryStruttura);
                    var slideCorrente = galleryStruttura.pswp.currIndex;
                    //console.log('change: slide corrente -> '+slideCorrente);
                    $('div.photoSwipe_innerthumbs img').removeClass("svifaded");
                    $('div.photoSwipe_innerthumbs img').eq(slideCorrente).addClass("svifaded");
                });

                galleryStruttura.on('close', function(){
                    //console.log('si chiude: rimuovo thumbs');
                    $('.photoSwipe_innerthumbs').remove();
                });

                $('body').on('click', 'div.photoSwipe_innerthumbs img', function(e) {
                    $('div.photoSwipe_innerthumbs img').removeClass("svifaded");
                    $(this).addClass('svifaded');
                    var thumbCliccata = $("div.photoSwipe_innerthumbs img").index($(this));
                    //console.log('clicco altra thumb '+thumbCliccata);
                    galleryStruttura.pswp.goTo(thumbCliccata);
                });

            }); // clicco su vedi tutte

        } // Lightbox - se ci sono foto struttura




        //Dimensioni Breadcrumbs
        if( $('#breadcrumbs').length > 0 ){
            if (winW <= 767) {
                var breadcrumbs = $('#breadcrumbs');
                var numBcAll = breadcrumbs.children().length;
                var numBcItems = breadcrumbs.children('a').length;
                //console.log(numBcAll);
                //console.log(numBcItems);

                //se sono più di 3 vanno sempre ristrette
                if (numBcItems > 3) {
                    breadcrumbs.find('a:first').addClass('bc-first');
                    breadcrumbs.find('a:last').addClass('bc-last');
                    breadcrumbs.find('a:not(.bc-first):not(.bc-last)').wrapInner('<span class="bc-txt"></span>');
                    breadcrumbs.find('a:not(.bc-first):not(.bc-last)').append('<i>...</i>');
                } else {
                    //altrimenti guardo se sono più lunghe della larghezza della finestra

                    //trovo tutte le larghezze dei figli 
                    var bcChildWidths = [];
                    breadcrumbs.children().each(function() {
                        var childW = $(this).outerWidth();
                        bcChildWidths.push(childW);
                    });
                    var bcGap = 7 * (numBcAll - 1); // 7 è il gap del flex tra gli elementi delle breadcrumbs
                    bcChildWidths.push(bcGap);

                    //le sommo
                    let bcWidth = 0;
                    for (let i = 0; i < bcChildWidths.length; i++) {
                        bcWidth += bcChildWidths[i];
                    }

                    //verifico se è più della larghezza finestra
                    if (bcWidth > winW) {
                        breadcrumbs.find('a:first').addClass('bc-first');
                        breadcrumbs.find('a:last').addClass('bc-last');
                        breadcrumbs.find('a:not(.bc-first):not(.bc-last)').wrapInner('<span class="bc-txt"></span>');
                        breadcrumbs.find('a:not(.bc-first):not(.bc-last)').append('<i>...</i>');
                        breadcrumbs.css('flex-wrap', 'nowrap');
                    }
                }
            }
        }
        

        //Leggi di più se div è più alto di 170px
        var txtEspandibile = $('.txt-expand');
        if (txtEspandibile.outerHeight() > 75) {
            // Rileva la lingua dalla pagina
            var lang = $('html').attr('lang') || 'it';
            var readMoreText = lang === 'en' ? 'Read more' : 'Leggi di più';
    
            txtEspandibile.addClass('txt-to-expand');
            txtEspandibile.after('<span class="txt-expand-open" data-lang="' + lang + '">' + readMoreText + '</span>');
        }
    }(jQuery)); //---window-load-----

// Gestione click sul pulsante
jQuery(document).on('click', '.txt-expand-open, .txt-expand-close', function() {
    var txtElement = jQuery(this).prev('.txt-expand');
    var lang = jQuery(this).attr('data-lang') || 'it';
    
    // Testi in base alla lingua
    var texts = {
        it: {
            readMore: 'Leggi di più',
            readLess: 'Riduci'
        },
        en: {
            readMore: 'Read more',
            readLess: 'Read less'
        }
    };
    
    if (jQuery(this).hasClass('txt-expand-open')) {
        // Se è "Leggi di più"
        txtElement
            .removeClass('txt-to-expand')
            .css('max-height', 'none');
        jQuery(this)
            .text(texts[lang].readLess)
            .removeClass('txt-expand-open')
            .addClass('txt-expand-close');
    } else {
        // Se è "Riduci"
        var maxHeight = '70px'; // Valore di default
        
        // Controllo se l'elemento è nel div intro-dettagli-more
        if (txtElement.closest('#intro-dettagli-more').length) {
            maxHeight = '70px';
        }
        // Controllo se l'elemento ha la classe recens-txt
        else if (txtElement.hasClass('recens-txt')) {
            maxHeight = '112px';
        }
        
        txtElement
            .addClass('txt-to-expand')
            .css('max-height', maxHeight);
        jQuery(this)
            .text(texts[lang].readMore)
            .removeClass('txt-expand-close')
            .addClass('txt-expand-open');
    }
});

    /*jQuery(document).on('loadedFilteredResults', '#results-container', function() {
        console.log('caricati tutti risultati!');
        ResizeSensor.reset();
    });*/


    // Nice select
    $('select').niceSelect();


    // ---------siblings
    $(".boxs").click(function(event) {
        $(this).toggleClass('active').siblings().removeClass('active');
    });


}(jQuery));