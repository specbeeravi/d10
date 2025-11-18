/**
 * Video: Play mp4 video when it is scrolled inito view.
 * 
 */

(function () {

    // Get all animation videos.
    var videos = document.querySelectorAll('.play-when-visible');

    // Define an easy to use forEach function because queryselector.forEach does not work on every browser.
    var forEach = function (array, callback, scope) {
        for (var i = 0; i < array.length; i++) {
            callback.call(scope, i, array[i]); // passes back stuff we need
        }
    };

    /*
     * Use IntersectionObserver to see if the video is visible.
     * See: https://developers.google.com/web/updates/2016/04/intersectionobserver#intersect_all_the_things
     */
    if (('IntersectionObserver' in window)) {

        const config = {
            // If the video gets within 50px in the Y axis, start loading the image.
            rootMargin: '50px 0px',
            threshold: 0.01
        };

        // Define our observer and observe each video.
        let observer = new IntersectionObserver(onIntersection, config);
        forEach(videos, function (index, video) {
            video.setAttribute("muted", "muted");
            observer.observe(video);
        });

        function onIntersection(entries) {
            // Loop through the entries
            forEach(entries, function (index, entry) {
                // Are we in viewport?
                if (entry.intersectionRatio > 0) {

                    //loadImage(entry.target);
                    playAnimation(entry.target, observer);
                    //entry.target.play();
                }
            });
        }

    }

    function playAnimation(videoElement, observer) {
        var promise = videoElement.play();

        if (promise !== undefined) {
            promise.then(_ => {
                videoElement.classList.remove("video-play-button");
                // Stop watching this element.
                observer.unobserve(videoElement);
            }).catch(error => {
                //console.log(error);

                // Autoplay was prevented.
                // Show a "Play" button so that user can start playback.
                // create wrapper container
                var wrapper = document.createElement('div');
                wrapper.classList.add("video-play-button");
                wrapper.addEventListener("click", removeAnimationButtonWrapper);

                // insert wrapper before el in the DOM tree
                videoElement.parentNode.insertBefore(wrapper, videoElement);
                // move el into wrapper
                wrapper.appendChild(videoElement);
                observer.unobserve(videoElement);
                //videoElement.classList.add("video-play-button");
            });
        }
    }

    function removeAnimationButtonWrapper(element) {
        element.target.parentNode.replaceWith(element.target);
        element.target.play();
    }

    /*
     * Fallback: just load images if browser does not support IntersectionObserver.
     */
    if (!('IntersectionObserver' in window)) {
        forEach(videos, function (index, video) {
            video.play();
        });
    }

}());
