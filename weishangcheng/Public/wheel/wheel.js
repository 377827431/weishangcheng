"use strict";
var appUtils = {};
appUtils.preset = {
	errorModal: {
		content: {
			html: "糟糕，网络不给力"
		},
		confirm: {
			html: "重新进入",
			click: function() {
				window.location.reload()
			}
		},
		cancel: "remove"
	}
},
appUtils.modal = function() {
	function n() {
		o && o.remove(),
		o = $($("#apps-modal-tpl").html()),
		i = {};
		for (var n in a) i[n] = o.find(".js-apps-modal-" + n)
	}
	function t(t) {
		n();
		var e, o, c, a;
		for (e in t) if ("string" != typeof t[e]) for (o in t[e]) c = i[e][o],
		$.isFunction(c) && c.call(i[e], t[e][o]);
		else a = t[e],
		"remove" == a && i[e].remove()
	}
	function e(n) {
		return $.extend(!0, {},
		a, n)
	}
	var o, i, c = {
		open: function(n) {
			t(e(n)),
			$(document.body).append(o)
		},
		close: function(n) {
			n === !0 ? o.find(".apps-modal").remove() : o && o.remove()
		}
	},
	a = {
		content: {
			html: ""
		},
		confirm: {
			html: "确定",
			click: $.noop
		},
		cancel: {
			html: "取消",
			click: c.close
		}
	};
	return c
} (),
appUtils.process = function() {
	function n(n) {
		a.cancel.html = n,
		l.cancel.html = n
	}
	function t() {
		return 0 !== i ? 10999 == i ? (c.open(a), !1) : 10998 == i ? (c.open(l), !1) : (c.open(s), !1) : 0 !== o.costPoint && void 0 != o.costPoint ? (c.open(r), !1) : !0
	}
	var e, o = _apps_global,
	i = o.error_code,
	c = appUtils.modal,
	a = {
		content: {
			html: o.error_msg
		},
		confirm: {
			html: "登录",
			click: function() {
				location.href = o.login
			}
		},
		cancel: {
			html: "取消",
			click: function() {
				c.close()
			}
		}
	},
	l = {
		content: {
			html: o.error_msg
		},
		confirm: {
			html: "关注",
			click: function() {
				c.close(),
				document.body.scrollTop = $('.qrcode').offset().top
			}
		},
		cancel: "remove"
	},
	r = {
		content: {
			html: '每次抽奖将消耗<span class="important"> ' + o.costPoint + "积分</span>"
		},
		confirm: {
			html: "赌一把",
			click: function() {
				c.close(),
				e.onconfirm && e.onconfirm()
			}
		},
		cancel: {
			html: "舍不得",
			click: function() {
				c.close()
			}
		}
	},
	s = {
		content: {
			html: o.error_msg
		},
		confirm: {
			html: "知道了",
			click: function() {
				c.close()
			}
		},
		cancel: "remove"
	};
	return e = {
		check: t,
		setCancelText: n,
		onconfirm: $.noop
	}
} (),
appUtils.atLeast = function(n, t) {
	function e() {
		c.resolve.apply(null, arguments)
	}
	var o, i = !1,
	c = {};
	return setTimeout(function() {
		i ? t.apply(null, o) : c.resolve = function() {
			t.apply(null, arguments)
		}
	},
	n),
	c.resolve = function() {
		o = arguments,
		i = !0
	},
	{
		resolve: e
	}
},
appUtils.randInt = function(n, t) {
	var e = n + Math.random() * (t - n);
	return parseInt(e, 10)
},
appUtils.format = function(n) {
	var t = Array.prototype.slice.call(arguments, 1);
	return n.replace(/{(\d+)}/g,
	function(n, e) {
		return "undefined" != typeof t[e] ? t[e] : n
	})
},
appUtils.getUrlParam = function(n, t) {
	var e = new RegExp("(^|&)" + n + "=([^&]*)(&|$)"),
	o = "router" === t ? window.location.href: window.location.search,
	i = o.substr(1).match(e);
	return null !== i ? window.unescape(i[2]) : null
},
function() {
	function n(n) {
		var t = Object.create(l);
		t.content = {
			html: n
		},
		a.open(t)
	}
	function t() {
		return $.ajax({
			url: i.post_url,
			data: {
				id: i.id
			},
			cache: !1,
			type: "post",
			dataType: "json",
			timeout: 5e3
		})
	}
	function e() {
		s.clear(),
		a.open(appUtils.preset.errorModal)
	}
	function o() {
		$(".js-start-btn").click(p),
		c.init = function() {}
	}
	var i = _apps_global,
	c = {},
	a = appUtils.modal,
	l = {
		content: {
			html: ""
		},
		confirm: {
			html: "知道了",
			click: function() {
				a.close()
			}
		},
		cancel: "remove"
	},
	r = {
		content: {
			html: ""
		},
		cancel: {
			html: "继续抽奖",
			click: function() {
				window.location.reload()
			}
		},
		confirm: {
			html: "查看奖品",
			click: function() {
				window.location.href = _userCenterUrl
			}
		}
	},
	s = function() {
		function n() {
			var n, t, e, o, c = i.prize, l;
			var $prizeList = $('.wheel');
			for (n in c) {
				t = c[n], 
				l = t.index.split(',');
				for(var ii=0; ii<l.length; ii++){
					e = $prizeList.find('[data-index="'+l[ii]+'"]').find(".wheel-icon"),
					o = t.image_url,
					o && o.length > 0 ? h(e, o) : t.point > 0 ? e.addClass("point-icon") : 0 === t.point && e.addClass("coupon-icon")
				}
			}
		}
		function t() {
			u = 0,
			d = !0,
			v = 100,
			f = function() {},
			l()
		}
		function e() {
			m.get(u).removeClass("active"),
			u++,
			u >= m.length && (u = 0),
			m.get(u).addClass("active"),
			p = setTimeout(e, v),
			f()
		}
		function o() {
			d = !1,
			clearTimeout(p)
		}
		function c() {
			d || (t(), e())
		}
		function a(n, t) {
			var e = 2 * m.length + (n - u);
			e > 2 * m.length && (e -= m.length),
			f = function() {
				v *= 1.08,
				e--,
				0 === e && (o(), t && setTimeout(t, 1400), m.getSelected().addClass("pulse"))
			}
		}
		function l() {
			$(".wheel-block").removeClass("active")
		}
		function r() {
			o(),
			l()
		}
		function s(n, t) {
			a(m.selectByCategory(n), t)
		}
		var u, p, f, m, d = !1,
		v = 100;
		m = function() {
			var n, t, e = [];
			return n = {
				set: function(t, o) {
					e[t] = o,
					n.length = e.length
				},
				get: function(n) {
					return e[n]
				},
				selectByCategory: function(n) {
					t = i = n;
					return t;
					var o = e.filter(function(t) {
						return t.hasClass(n)
					}),
					i = appUtils.randInt(0, o.length);
					return t = o[i].data("index")
				},
				getSelected: function() {
					return e[t]
				},
				length: 0
			},
			n.splice = [].splice,
			n.constructor = Array,
			n
		} ();
		var h = function() {
			var n = {},
			t = function(n, t) {
				n.addClass("animated tada"),
				setTimeout(function() {
					n.addClass("custom-icon").removeClass("animated tada").css("backgroundImage", "url(" + t + ")")
				},
				1200)
			};
			return function(e, o) { / !\d / .test(o) || (o += "!100x100.jpg");
				var i = function() {
					t(e, o)
				};
				if (n[o]) i();
				else {
					n[o] = !0;
					var c = new Image;
					c.onload = i,
					c.src = o
				}
			}
		} ();
		return $(".wheel-block").each(function() {
			var n = $(this),
			t = n.data("index");
			n.addClass("animated"),
			m.set(t, n)
		}),
		n(),
		{
			start: c,
			clear: r,
			to: s
		}
	} (),
	u = function() {
		var t = '<span class="important">{0}！</span>',
		e = "" === i.failedInfo ? "哎呀，真可惜擦身而过!": i.failedInfo,
		o = '<br>手气这么好，再送您 <span class="important">{0}</span> 积分',
		c = "运气不错，您还可以再玩一次",
		l = '<br>送您 <span class="important">{0}</span> 积分';
		return function(u) {
			var p = "";
			var f = u.data;
			if (0 === u.code) {
				switch (f.type) {
					case 0:
						p = f.title;
						if(f.give_point > 0) p += appUtils.format(l, f.give_point);
						break;
					default:
						p = appUtils.format(t, '哇！抽中 ' + f.title);
					    if(f.give_point > 0) p += appUtils.format(o, f.give_point);
					break;
				}
				s.to(f.index,
				function() {
					3 == f.type && (r.confirm.html = "立即兑换", r.cancel = "remove"),
					r.content.html = p;
					if(f.detail_url && f.detail_url.length > 0){
						r.confirm = {
								html: "查看奖品",
								click: function() {
									window.location.href = f.detail_url
								}
							},
							$(".js-view-prize").attr("href", f.detail_url)
					}else{
						r.confirm = "remove"
					}
					a.open(r)
				})
			}else if(10998 == u.code){
				s.to(f.index, function() {
					r.cancel = "remove",
					r.confirm.html = "立即关注",
					r.content.html = u.msg,
					r.confirm.click = function(){
						a.close(),
						document.body.scrollTop = $('.qrcode').offset().top
					},
					a.open(r)
				})
			}else 2 == i.lotteryAgain ? (p = u.msg || c, s.to(f.index,
			function() {
				n(p)
			})) : (p = u.msg, s.to(f.index,
			function() {
				n(p)
			})),
			void 0 != i.givePoint && 0 != i.givePoint && (p += appUtils.format(l, i.givePoint))
		}
	} (),
	p = function() {
		var n = !1;
		return function() {
			if (!n) {
				n = !0,
				s.start();
				var o = appUtils.atLeast(1500, u),
				i = appUtils.atLeast(1500, e);
				t().done(o.resolve).fail(i.resolve)
			}
		}
	} ();
	c.init = o,
	window.gameIns = c
} (),
$(function() {
	var n = $(".js-start-btn");
	appUtils.process.check() ? gameIns.init() : n.on("click",
	function(n) {
		appUtils.process.check()
	}),
	appUtils.process.onconfirm = function() {
		n.unbind("click"),
		gameIns.init()
	},
	_apps_global.has_point || $(".js-activity-note").remove()
},
!1);