"use strict";
const stybleElements = document.querySelectorAll('.styble-video');
if (stybleElements.length > 0) {

    stybleElements.forEach((item) => {
        let itemWrap = item.querySelector('.styble-video-wrap');
        let itemData = JSON.parse(itemWrap.getAttribute('data-video'));
        let popup = item.querySelector('.styble-video-popup-modal');

        if(itemData.showImageOverlay || itemData.enablePopup) {
            item.classList.add('styble-video-wrap-overlay');
        }

        const closePopup = () => {
            itemWrap.style.display = 'none';
            item.classList.add('styble-video-wrap-overlay');
            item.classList.remove('styble-video-popup');
            renderReactPlayer(itemWrap, { url: itemData.url, playing: false });
        };

        if (itemData.enablePopup) {
            itemWrap.style.display = 'none';

            item.querySelector('.styble-video-cross-icon').addEventListener('click', function () {
                closePopup();
            })

            item.querySelector('.styble-video-popup-overlay').addEventListener('click', function () {
                closePopup();
            })


            popup.addEventListener('click', function () {
                item.classList.add('styble-video-popup');
                item.classList.remove('styble-video-wrap-overlay');
                itemWrap.style.display = 'block';
                renderReactPlayer(itemWrap, { width: '100%', height: '100%', url: itemData.url, controls: itemData.controls, loop: itemData.loop, muted: itemData.muted, playing: itemData.playing });

                let getBoundingClientRect = itemWrap.getBoundingClientRect();
                let crossIcon = item.querySelector('.styble-video-cross-icon');

                crossIcon.setAttribute('style', `left: calc(${getBoundingClientRect.x - 10 }px + ${getBoundingClientRect.width}px); top: ${getBoundingClientRect.top - 40}px`);
            })
        } else {

            renderReactPlayer(itemWrap, { width: '100%', url: itemData.url, controls: itemData.controls, loop: itemData.loop, muted: itemData.muted, playing: itemData.playing, playIcon: '\'' });

            if(itemData.showImageOverlay) {
                const playIconHtml = () => {
                    if ('icon' === itemData.playIcon.iconType) {
                        return (
                            `<div class='styble-play-icon'><span class="${itemData.playIcon.icon}"></span></div>`
                        )
                    }
                    if ('image' === itemData.playIcon.iconType) {
                        return (
                            `<div class='styble-play-icon'><img src="${itemData.playIcon.icon}" /></div>`
                        )
                    }
                    if ('none' === itemData.playIcon.iconType) {
                        return `<span></span>`
                    }
                }

                itemWrap.insertAdjacentHTML('afterend', playIconHtml());

                let playIcon = item.querySelector('.styble-play-icon');

                playIcon.addEventListener('click', (e) => {

                    item.classList.remove('styble-video-wrap-overlay');
                    playIcon.style.display = 'none';

                    let iframeSrc = item.querySelector('iframe').src.replace("autoplay=0", 'autoplay=1');

                    item.querySelector('iframe').src = iframeSrc;
                })
            }
        }


    })
}

(function ($) {
    $(document).on('ready', () => {
        $('.styble-post-grid').on('click', '.styble-post-pagination a', function (e) {
            e.preventDefault();
            let pagedNum = e.target.closest('a').innerText;
            if ($(this).hasClass('next')) {
                pagedNum = parseInt($(this).parents('.styble-post-pagination').find('a.current')[0].innerText) + 1;
            }
            if ($(this).hasClass('prev')) {
                pagedNum = parseInt($(this).parents('.styble-post-pagination').find('a.current')[0].innerText) - 1;
            }
            if ($(this).hasClass('current') || !parseInt( pagedNum )) {
                return;
            }
            let data_query = $(this).parents('.styble-post-grid').attr('data_query');
            data_query = JSON.parse(data_query);
            let data_attr = $(this).parents('.styble-post-grid').attr('data_attributes');
            data_attr = JSON.parse(data_attr);
            let nonce = stybleLocalize.nonce;
            $.ajax({
                url: stybleLocalize.ajaxUrl,
                type: 'post',
                data: {
                    action: 'styble_pagination',
                    paged: parseInt( pagedNum ),
                    data_query,
                    data_attr,
                    nonce,
                },
                success: (data) => {
                    let offset = $(this).parents('.wp-block-styble-post-grid').offset();
                    $("html").animate({ scrollTop: offset.top - 60 }, 100);
                    $(this).parents('.wp-block-styble-post-grid').find('.styble-post-grid').html(data.data);
                    $.getScript(`${stybleLocalize.pluginUrl}assets/js/masonry.js`);
                }
            })
        })
    })
})(jQuery)