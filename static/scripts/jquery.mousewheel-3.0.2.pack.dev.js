!function(b) {
    function d(a) {
        var f = [].slice.call(arguments, 1), e = 0;
        return a = b.event.fix(a || window.event), a.type = "mousewheel", a.wheelDelta && (e = a.wheelDelta / 120), 
        a.detail && (e = -a.detail / 3), f.unshift(a, e), b.event.handle.apply(this, f);
    }
    var c = [ "DOMMouseScroll", "mousewheel" ];
    b.event.special.mousewheel = {
        setup: function() {
            if (this.addEventListener) for (var a = c.length; a; ) this.addEventListener(c[--a], d, !1); else this.onmousewheel = d;
        },
        teardown: function() {
            if (this.removeEventListener) for (var a = c.length; a; ) this.removeEventListener(c[--a], d, !1); else this.onmousewheel = null;
        }
    }, b.fn.extend({
        mousewheel: function(a) {
            return a ? this.bind("mousewheel", a) : this.trigger("mousewheel");
        },
        unmousewheel: function(a) {
            return this.unbind("mousewheel", a);
        }
    });
}(jQuery);