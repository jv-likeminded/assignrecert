YUI.add(
  "moodle-assignrecertfeedback_editpdf-editor",
  function (e, t) {
    var n = M.cfg.wwwroot + "/mod/assignrecert/feedback/editpdf/ajax.php",
      r =
        M.cfg.wwwroot + "/mod/assignrecert/feedback/editpdf/ajax_progress.php",
      i = { DIALOGUE: "assignrecertfeedback_editpdf_widget" },
      s = {
        PREVIOUSBUTTON: ".navigate-previous-button",
        NEXTBUTTON: " .navigate-next-button",
        SEARCHCOMMENTSBUTTON: ".searchcommentsbutton",
        SEARCHFILTER: ".assignrecertfeedback_editpdf_commentsearch input",
        SEARCHCOMMENTSLIST: ".assignrecertfeedback_editpdf_commentsearch ul",
        PAGESELECT: ".navigate-page-select",
        LOADINGICON: ".loading",
        PROGRESSBARCONTAINER: ".progress-info.progress-striped",
        DRAWINGREGION: ".drawingregion",
        DRAWINGCANVAS: ".drawingcanvas",
        SAVE: ".savebutton",
        COMMENTCOLOURBUTTON: ".commentcolourbutton",
        COMMENTMENU: ".commentdrawable a",
        ANNOTATIONCOLOURBUTTON: ".annotationcolourbutton",
        DELETEANNOTATIONBUTTON: ".deleteannotationbutton",
        UNSAVEDCHANGESDIV: ".assignrecertfeedback_editpdf_unsavedchanges",
        UNSAVEDCHANGESINPUT:
          'input[name="assignrecertfeedback_editpdf_haschanges"]',
        STAMPSBUTTON: ".currentstampbutton",
        DIALOGUE: "." + i.DIALOGUE,
      },
      o = "rgba(200, 200, 255, 0.9)",
      u = "rgba(200, 200, 255, 0.5)",
      a = "rgb(51, 51, 51)",
      f = {
        white: "rgb(255,255,255)",
        yellow: "rgb(255,236,174)",
        red: "rgb(249,181,179)",
        green: "rgb(214,234,178)",
        blue: "rgb(203,217,237)",
        clear: "rgba(255,255,255, 0)",
      },
      l = {
        white: "rgb(255,255,255)",
        yellow: "rgb(255,207,53)",
        red: "rgb(239,69,64)",
        green: "rgb(152,202,62)",
        blue: "rgb(125,159,211)",
        black: "rgb(51,51,51)",
      },
      c = 300,
      h = {
        comment: ".commentbutton",
        pen: ".penbutton",
        line: ".linebutton",
        rectangle: ".rectanglebutton",
        oval: ".ovalbutton",
        stamp: ".stampbutton",
        select: ".selectbutton",
        drag: ".dragbutton",
        highlight: ".highlightbutton",
      },
      p = 4,
      d = function (e, t) {
        (this.x = parseInt(e, 10)),
          (this.y = parseInt(t, 10)),
          (this.clip = function (e) {
            return (
              this.x < e.x && (this.x = e.x),
              this.x > e.x + e.width && (this.x = e.x + e.width),
              this.y < e.y && (this.y = e.y),
              this.y > e.y + e.height && (this.y = e.y + e.height),
              this
            );
          });
      };
    (M.assignrecertfeedback_editpdf = M.assignrecertfeedback_editpdf || {}),
      (M.assignrecertfeedback_editpdf.point = d);
    var v = function (e, t, n, r) {
      (this.x = e),
        (this.y = t),
        (this.width = n),
        (this.height = r),
        (this.bound = function (e) {
          var t = 0,
            n = 0,
            r = 0,
            i = 0,
            s = 0,
            o;
          for (s = 0; s < e.length; s++) {
            o = e[s];
            if (o.x < t || s === 0) t = o.x;
            if (o.x > n || s === 0) n = o.x;
            if (o.y < r || s === 0) r = o.y;
            if (o.y > i || s === 0) i = o.y;
          }
          return (
            (this.x = t),
            (this.y = r),
            (this.width = n - t),
            (this.height = i - r),
            this
          );
        }),
        (this.has_min_width = function () {
          return this.width >= 5;
        }),
        (this.has_min_height = function () {
          return this.height >= 5;
        }),
        (this.set_min_width = function () {
          this.width = 5;
        }),
        (this.set_min_height = function () {
          this.height = 5;
        });
    };
    (M.assignrecertfeedback_editpdf = M.assignrecertfeedback_editpdf || {}),
      (M.assignrecertfeedback_editpdf.rect = v);
    var m = function () {
      (this.start = !1),
        (this.end = !1),
        (this.starttime = 0),
        (this.annotationstart = !1),
        (this.tool = "drag"),
        (this.commentcolour = "yellow"),
        (this.annotationcolour = "red"),
        (this.stamp = ""),
        (this.path = []);
    };
    (M.assignrecertfeedback_editpdf = M.assignrecertfeedback_editpdf || {}),
      (M.assignrecertfeedback_editpdf.edit = m);
    var g = function (e) {
      (this.editor = e),
        (this.shapes = []),
        (this.nodes = []),
        (this.erase = function () {
          if (this.shapes)
            while (this.shapes.length > 0)
              this.editor.graphic.removeShape(this.shapes.pop());
          if (this.nodes)
            while (this.nodes.length > 0) this.nodes.pop().remove();
        }),
        (this.scroll_update = function (e, t) {
          var n, r, i;
          for (n = 0; n < this.nodes.length; n++)
            (r = this.nodes[n].getData("x")),
              (i = this.nodes[n].getData("y")),
              r !== undefined &&
                i !== undefined &&
                (this.nodes[n].setX(parseInt(r, 10) - e),
                this.nodes[n].setY(parseInt(i, 10) - t));
        }),
        (this.store_position = function (e, t, n) {
          var r, i, o;
          (r = this.editor.get_dialogue_element(s.DRAWINGREGION)),
            (i = parseInt(r.get("scrollLeft"), 10)),
            (o = parseInt(r.get("scrollTop"), 10)),
            e.setData("x", t + i),
            e.setData("y", n + o);
        });
    };
    (M.assignrecertfeedback_editpdf = M.assignrecertfeedback_editpdf || {}),
      (M.assignrecertfeedback_editpdf.drawable = g);
    var y = function (e) {
      y.superclass.constructor.apply(this, [e]);
    };
    (y.NAME = "annotation"),
      (y.ATTRS = {}),
      e.extend(y, e.Base, {
        editor: null,
        gradeid: 0,
        pageno: 0,
        x: 0,
        y: 0,
        endx: 0,
        endy: 0,
        path: "",
        type: "rect",
        colour: "red",
        drawable: !1,
        initializer: function (e) {
          (this.editor = e.editor || null),
            (this.gradeid = parseInt(e.gradeid, 10) || 0),
            (this.pageno = parseInt(e.pageno, 10) || 0),
            (this.x = parseInt(e.x, 10) || 0),
            (this.y = parseInt(e.y, 10) || 0),
            (this.endx = parseInt(e.endx, 10) || 0),
            (this.endy = parseInt(e.endy, 10) || 0),
            (this.path = e.path || ""),
            (this.type = e.type || "rect"),
            (this.colour = e.colour || "red"),
            (this.drawable = !1);
        },
        clean: function () {
          return {
            gradeid: this.gradeid,
            x: parseInt(this.x, 10),
            y: parseInt(this.y, 10),
            endx: parseInt(this.endx, 10),
            endy: parseInt(this.endy, 10),
            type: this.type,
            path: this.path,
            pageno: this.pageno,
            colour: this.colour,
          };
        },
        draw_highlight: function () {
          var t,
            n = this.editor.get_dialogue_element(s.DRAWINGREGION),
            r = this.editor.get_dialogue_element(s.DRAWINGCANVAS).getXY(),
            i;
          if (this.editor.currentannotation === this) {
            (t = new M.assignrecertfeedback_editpdf.rect()),
              t.bound([
                new M.assignrecertfeedback_editpdf.point(this.x, this.y),
                new M.assignrecertfeedback_editpdf.point(this.endx, this.endy),
              ]),
              (i = this.editor.graphic.addShape({
                type: e.Rect,
                width: t.width,
                height: t.height,
                stroke: { weight: p, color: o },
                fill: { color: u },
                x: t.x,
                y: t.y,
              })),
              this.drawable.shapes.push(i);
            var a = e.Node.create(
                '<img src="' +
                  M.util.image_url("trash", "assignrecertfeedback_editpdf") +
                  '"/>'
              ),
              f = e.Node.create('<a href="#" role="button"></a>');
            a.setAttrs({
              alt: M.util.get_string(
                "deleteannotation",
                "assignrecertfeedback_editpdf"
              ),
            }),
              a.setStyles({ backgroundColor: "white" }),
              f.addClass("deleteannotationbutton"),
              f.append(a),
              n.append(f),
              f.setData("annotation", this),
              f.setStyle("zIndex", "200"),
              f.on("click", this.remove, this),
              f.on("key", this.remove, "space,enter", this),
              f.setX(r[0] + t.x + t.width - 18),
              f.setY(r[1] + t.y + 6),
              this.drawable.nodes.push(f);
          }
          return this.drawable;
        },
        draw: function () {
          return this.draw_highlight(), this.drawable;
        },
        remove: function (e) {
          var t, n;
          e.preventDefault(),
            (t = this.editor.pages[this.editor.currentpage].annotations);
          for (n = 0; n < t.length; n++)
            if (t[n] === this) {
              t.splice(n, 1),
                this.drawable && this.drawable.erase(),
                (this.editor.currentannotation = !1),
                this.editor.save_current_page();
              return;
            }
        },
        move: function (t, n) {
          var r = t - this.x,
            i = n - this.y,
            s,
            o,
            u,
            a,
            f;
          (this.x += r),
            (this.y += i),
            (this.endx += r),
            (this.endy += i),
            this.path &&
              ((s = []),
              (o = this.path.split(":")),
              e.each(o, function (e) {
                (u = e.split(",")),
                  (a = parseInt(u[0], 10)),
                  (f = parseInt(u[1], 10)),
                  s.push(a + r + "," + (f + i));
              }),
              (this.path = s.join(":"))),
            this.drawable && this.drawable.erase(),
            this.editor.drawables.push(this.draw());
        },
        draw_current_edit: function (e) {
          var t = e && !1;
          return t;
        },
        init_from_edit: function (e) {
          var t = new M.assignrecertfeedback_editpdf.rect();
          return (
            t.bound([e.start, e.end]),
            (this.gradeid = this.editor.get("gradeid")),
            (this.pageno = this.editor.currentpage),
            (this.x = t.x),
            (this.y = t.y),
            (this.endx = t.x + t.width),
            (this.endy = t.y + t.height),
            (this.colour = e.annotationcolour),
            (this.path = ""),
            t.has_min_width() && t.has_min_height()
          );
        },
      }),
      (M.assignrecertfeedback_editpdf = M.assignrecertfeedback_editpdf || {}),
      (M.assignrecertfeedback_editpdf.annotation = y);
    var b = function (e) {
      b.superclass.constructor.apply(this, [e]);
    };
    (b.NAME = "annotationline"),
      (b.ATTRS = {}),
      e.extend(b, M.assignrecertfeedback_editpdf.annotation, {
        draw: function () {
          var t, n;
          return (
            (t = new M.assignrecertfeedback_editpdf.drawable(this.editor)),
            (n = this.editor.graphic.addShape({
              type: e.Path,
              fill: !1,
              stroke: { weight: p, color: l[this.colour] },
            })),
            n.moveTo(this.x, this.y),
            n.lineTo(this.endx, this.endy),
            n.end(),
            t.shapes.push(n),
            (this.drawable = t),
            b.superclass.draw.apply(this)
          );
        },
        draw_current_edit: function (t) {
          var n = new M.assignrecertfeedback_editpdf.drawable(this.editor),
            r;
          return (
            (r = this.editor.graphic.addShape({
              type: e.Path,
              fill: !1,
              stroke: { weight: p, color: l[t.annotationcolour] },
            })),
            r.moveTo(t.start.x, t.start.y),
            r.lineTo(t.end.x, t.end.y),
            r.end(),
            n.shapes.push(r),
            n
          );
        },
        init_from_edit: function (e) {
          return (
            (this.gradeid = this.editor.get("gradeid")),
            (this.pageno = this.editor.currentpage),
            (this.x = e.start.x),
            (this.y = e.start.y),
            (this.endx = e.end.x),
            (this.endy = e.end.y),
            (this.colour = e.annotationcolour),
            (this.path = ""),
            this.endx - this.x !== 0 || this.endy - this.y !== 0
          );
        },
      }),
      (M.assignrecertfeedback_editpdf = M.assignrecertfeedback_editpdf || {}),
      (M.assignrecertfeedback_editpdf.annotationline = b);
    var w = function (e) {
      w.superclass.constructor.apply(this, [e]);
    };
    (w.NAME = "annotationrectangle"),
      (w.ATTRS = {}),
      e.extend(w, M.assignrecertfeedback_editpdf.annotation, {
        draw: function () {
          var t, n, r;
          return (
            (t = new M.assignrecertfeedback_editpdf.drawable(this.editor)),
            (n = new M.assignrecertfeedback_editpdf.rect()),
            n.bound([
              new M.assignrecertfeedback_editpdf.point(this.x, this.y),
              new M.assignrecertfeedback_editpdf.point(this.endx, this.endy),
            ]),
            (r = this.editor.graphic.addShape({
              type: e.Rect,
              width: n.width,
              height: n.height,
              stroke: { weight: p, color: l[this.colour] },
              x: n.x,
              y: n.y,
            })),
            t.shapes.push(r),
            (this.drawable = t),
            w.superclass.draw.apply(this)
          );
        },
        draw_current_edit: function (t) {
          var n = new M.assignrecertfeedback_editpdf.drawable(this.editor),
            r,
            i;
          return (
            (i = new M.assignrecertfeedback_editpdf.rect()),
            i.bound([
              new M.assignrecertfeedback_editpdf.point(t.start.x, t.start.y),
              new M.assignrecertfeedback_editpdf.point(t.end.x, t.end.y),
            ]),
            i.has_min_width() || i.set_min_width(),
            i.has_min_height() || i.set_min_height(),
            (r = this.editor.graphic.addShape({
              type: e.Rect,
              width: i.width,
              height: i.height,
              stroke: { weight: p, color: l[t.annotationcolour] },
              x: i.x,
              y: i.y,
            })),
            n.shapes.push(r),
            n
          );
        },
      }),
      (M.assignrecertfeedback_editpdf = M.assignrecertfeedback_editpdf || {}),
      (M.assignrecertfeedback_editpdf.annotationrectangle = w);
    var E = function (e) {
      E.superclass.constructor.apply(this, [e]);
    };
    (E.NAME = "annotationoval"),
      (E.ATTRS = {}),
      e.extend(E, M.assignrecertfeedback_editpdf.annotation, {
        draw: function () {
          var t, n, r;
          return (
            (t = new M.assignrecertfeedback_editpdf.drawable(this.editor)),
            (n = new M.assignrecertfeedback_editpdf.rect()),
            n.bound([
              new M.assignrecertfeedback_editpdf.point(this.x, this.y),
              new M.assignrecertfeedback_editpdf.point(this.endx, this.endy),
            ]),
            (r = this.editor.graphic.addShape({
              type: e.Ellipse,
              width: n.width,
              height: n.height,
              stroke: { weight: p, color: l[this.colour] },
              x: n.x,
              y: n.y,
            })),
            t.shapes.push(r),
            (this.drawable = t),
            E.superclass.draw.apply(this)
          );
        },
        draw_current_edit: function (t) {
          var n = new M.assignrecertfeedback_editpdf.drawable(this.editor),
            r,
            i;
          return (
            (i = new M.assignrecertfeedback_editpdf.rect()),
            i.bound([
              new M.assignrecertfeedback_editpdf.point(t.start.x, t.start.y),
              new M.assignrecertfeedback_editpdf.point(t.end.x, t.end.y),
            ]),
            i.has_min_width() || i.set_min_width(),
            i.has_min_height() || i.set_min_height(),
            (r = this.editor.graphic.addShape({
              type: e.Ellipse,
              width: i.width,
              height: i.height,
              stroke: { weight: p, color: l[t.annotationcolour] },
              x: i.x,
              y: i.y,
            })),
            n.shapes.push(r),
            n
          );
        },
      }),
      (M.assignrecertfeedback_editpdf = M.assignrecertfeedback_editpdf || {}),
      (M.assignrecertfeedback_editpdf.annotationoval = E);
    var S = function (e) {
      S.superclass.constructor.apply(this, [e]);
    };
    (S.NAME = "annotationpen"),
      (S.ATTRS = {}),
      e.extend(S, M.assignrecertfeedback_editpdf.annotation, {
        draw: function () {
          var t, n, r, i, s;
          return (
            (t = new M.assignrecertfeedback_editpdf.drawable(this.editor)),
            (n = this.editor.graphic.addShape({
              type: e.Path,
              fill: !1,
              stroke: { weight: p, color: l[this.colour] },
            })),
            (r = !0),
            (i = this.path.split(":")),
            e.each(
              i,
              function (e) {
                (s = e.split(",")),
                  r ? (n.moveTo(s[0], s[1]), (r = !1)) : n.lineTo(s[0], s[1]);
              },
              this
            ),
            n.end(),
            t.shapes.push(n),
            (this.drawable = t),
            S.superclass.draw.apply(this)
          );
        },
        draw_current_edit: function (t) {
          var n = new M.assignrecertfeedback_editpdf.drawable(this.editor),
            r,
            i;
          return (
            (r = this.editor.graphic.addShape({
              type: e.Path,
              fill: !1,
              stroke: { weight: p, color: l[t.annotationcolour] },
            })),
            (i = !0),
            e.each(
              t.path,
              function (e) {
                i ? (r.moveTo(e.x, e.y), (i = !1)) : r.lineTo(e.x, e.y);
              },
              this
            ),
            r.end(),
            n.shapes.push(r),
            n
          );
        },
        init_from_edit: function (e) {
          var t = new M.assignrecertfeedback_editpdf.rect(),
            n = [],
            r = 0;
          t.bound(e.path);
          for (r = 0; r < e.path.length; r++)
            n.push(parseInt(e.path[r].x, 10) + "," + parseInt(e.path[r].y, 10));
          return (
            (this.gradeid = this.editor.get("gradeid")),
            (this.pageno = this.editor.currentpage),
            (this.x = t.x),
            (this.y = t.y),
            (this.endx = t.x + t.width),
            (this.endy = t.y + t.height),
            (this.colour = e.annotationcolour),
            (this.path = n.join(":")),
            t.has_min_width() || t.has_min_height()
          );
        },
      }),
      (M.assignrecertfeedback_editpdf = M.assignrecertfeedback_editpdf || {}),
      (M.assignrecertfeedback_editpdf.annotationpen = S);
    var x = function (e) {
      x.superclass.constructor.apply(this, [e]);
    };
    (x.NAME = "annotationhighlight"),
      (x.ATTRS = {}),
      e.extend(x, M.assignrecertfeedback_editpdf.annotation, {
        draw: function () {
          var t, n, r, i;
          return (
            (t = new M.assignrecertfeedback_editpdf.drawable(this.editor)),
            (r = new M.assignrecertfeedback_editpdf.rect()),
            r.bound([
              new M.assignrecertfeedback_editpdf.point(this.x, this.y),
              new M.assignrecertfeedback_editpdf.point(this.endx, this.endy),
            ]),
            (i = l[this.colour]),
            (i = i.replace("rgb", "rgba")),
            (i = i.replace(")", ",0.5)")),
            (n = this.editor.graphic.addShape({
              type: e.Rect,
              width: r.width,
              height: r.height,
              stroke: !1,
              fill: { color: i },
              x: r.x,
              y: r.y,
            })),
            t.shapes.push(n),
            (this.drawable = t),
            x.superclass.draw.apply(this)
          );
        },
        draw_current_edit: function (t) {
          var n = new M.assignrecertfeedback_editpdf.drawable(this.editor),
            r,
            i,
            s;
          return (
            (i = new M.assignrecertfeedback_editpdf.rect()),
            i.bound([
              new M.assignrecertfeedback_editpdf.point(t.start.x, t.start.y),
              new M.assignrecertfeedback_editpdf.point(t.end.x, t.end.y),
            ]),
            i.has_min_width() || i.set_min_width(),
            (s = l[t.annotationcolour]),
            (s = s.replace("rgb", "rgba")),
            (s = s.replace(")", ",0.5)")),
            (r = this.editor.graphic.addShape({
              type: e.Rect,
              width: i.width,
              height: 16,
              stroke: !1,
              fill: { color: s },
              x: i.x,
              y: t.start.y,
            })),
            n.shapes.push(r),
            n
          );
        },
        init_from_edit: function (e) {
          var t = new M.assignrecertfeedback_editpdf.rect();
          return (
            t.bound([e.start, e.end]),
            (this.gradeid = this.editor.get("gradeid")),
            (this.pageno = this.editor.currentpage),
            (this.x = t.x),
            (this.y = e.start.y),
            (this.endx = t.x + t.width),
            (this.endy = e.start.y + 16),
            (this.colour = e.annotationcolour),
            (this.page = ""),
            t.has_min_width()
          );
        },
      }),
      (M.assignrecertfeedback_editpdf = M.assignrecertfeedback_editpdf || {}),
      (M.assignrecertfeedback_editpdf.annotationhighlight = x);
    var T = function (e) {
      T.superclass.constructor.apply(this, [e]);
    };
    (T.NAME = "annotationstamp"),
      (T.ATTRS = {}),
      e.extend(T, M.assignrecertfeedback_editpdf.annotation, {
        draw: function () {
          var t = new M.assignrecertfeedback_editpdf.drawable(this.editor),
            n = this.editor.get_dialogue_element(s.DRAWINGCANVAS),
            r,
            i;
          return (
            (i = this.editor.get_window_coordinates(
              new M.assignrecertfeedback_editpdf.point(this.x, this.y)
            )),
            (r = e.Node.create("<div/>")),
            r.setStyles({
              position: "absolute",
              display: "inline-block",
              backgroundImage:
                "url(" + this.editor.get_stamp_image_url(this.path) + ")",
              width: this.endx - this.x,
              height: this.endy - this.y,
              backgroundSize: "100% 100%",
              zIndex: 50,
            }),
            n.append(r),
            r.setX(i.x),
            r.setY(i.y),
            t.store_position(r, i.x, i.y),
            this.editor.get("readonly") ||
              (r.on(
                "gesturemovestart",
                this.editor.edit_start,
                null,
                this.editor
              ),
              r.on("gesturemove", this.editor.edit_move, null, this.editor),
              r.on("gesturemoveend", this.editor.edit_end, null, this.editor)),
            t.nodes.push(r),
            (this.drawable = t),
            T.superclass.draw.apply(this)
          );
        },
        draw_current_edit: function (t) {
          var n = new M.assignrecertfeedback_editpdf.rect(),
            r = new M.assignrecertfeedback_editpdf.drawable(this.editor),
            i = this.editor.get_dialogue_element(s.DRAWINGREGION),
            o,
            u;
          return (
            n.bound([t.start, t.end]),
            (u = this.editor.get_window_coordinates(
              new M.assignrecertfeedback_editpdf.point(n.x, n.y)
            )),
            (o = e.Node.create("<div/>")),
            o.setStyles({
              position: "absolute",
              display: "inline-block",
              backgroundImage:
                "url(" + this.editor.get_stamp_image_url(t.stamp) + ")",
              width: n.width,
              height: n.height,
              backgroundSize: "100% 100%",
              zIndex: 50,
            }),
            i.append(o),
            o.setX(u.x),
            o.setY(u.y),
            r.store_position(o, u.x, u.y),
            r.nodes.push(o),
            r
          );
        },
        init_from_edit: function (e) {
          var t = new M.assignrecertfeedback_editpdf.rect();
          return (
            t.bound([e.start, e.end]),
            t.width < 40 && (t.width = 40),
            t.height < 40 && (t.height = 40),
            (this.gradeid = this.editor.get("gradeid")),
            (this.pageno = this.editor.currentpage),
            (this.x = t.x),
            (this.y = t.y),
            (this.endx = t.x + t.width),
            (this.endy = t.y + t.height),
            (this.colour = e.annotationcolour),
            (this.path = e.stamp),
            !0
          );
        },
        move: function (e, t) {
          var n = e - this.x,
            r = t - this.y;
          (this.x += n),
            (this.y += r),
            (this.endx += n),
            (this.endy += r),
            this.drawable && this.drawable.erase(),
            this.editor.drawables.push(this.draw());
        },
      }),
      (M.assignrecertfeedback_editpdf = M.assignrecertfeedback_editpdf || {}),
      (M.assignrecertfeedback_editpdf.annotationstamp = T);
    var N = "Dropdown menu",
      C;
    (C = function (e) {
      (e.draggable = !1),
        (e.centered = !1),
        (e.width = "auto"),
        (e.visible = !1),
        (e.footerContent = ""),
        C.superclass.constructor.apply(this, [e]);
    }),
      e.extend(
        C,
        M.core.dialogue,
        {
          initializer: function (t) {
            var n, r, i, s;
            C.superclass.initializer.call(this, t),
              (s = this.get("boundingBox")),
              s.addClass("assignrecertfeedback_editpdf_dropdown"),
              (n = this.get("buttonNode")),
              (r = this.bodyNode),
              (i = e.Node.create("<h3/>")),
              i.addClass("accesshide"),
              i.setHTML(this.get("headerText")),
              r.prepend(i),
              r.on(
                "clickoutside",
                function (e) {
                  this.get("visible") &&
                    e.target.get("id") !== n.get("id") &&
                    e.target.ancestor().get("id") !== n.get("id") &&
                    (e.preventDefault(), this.hide());
                },
                this
              ),
              n.on(
                "click",
                function (e) {
                  e.preventDefault(), this.show();
                },
                this
              ),
              n.on("key", this.show, "enter,space", this);
          },
          show: function () {
            var t = this.get("buttonNode"),
              n = C.superclass.show.call(this);
            return (
              this.align(t, [
                e.WidgetPositionAlign.TL,
                e.WidgetPositionAlign.BL,
              ]),
              n
            );
          },
        },
        {
          NAME: N,
          ATTRS: { headerText: { value: "" }, buttonNode: { value: null } },
        }
      ),
      e.Base.modifyAttrs(C, {
        modal: {
          getter: function () {
            return !1;
          },
        },
      }),
      (M.assignrecertfeedback_editpdf = M.assignrecertfeedback_editpdf || {}),
      (M.assignrecertfeedback_editpdf.dropdown = C);
    var k = "Colourpicker",
      L;
    (L = function (e) {
      L.superclass.constructor.apply(this, [e]);
    }),
      e.extend(
        L,
        M.assignrecertfeedback_editpdf.dropdown,
        {
          initializer: function (t) {
            var n = e.Node.create(
                '<ul role="menu" class="assignrecertfeedback_editpdf_menu"/>'
              ),
              r;
            e.each(
              this.get("colours"),
              function (t, r) {
                var i, s, o, u, a;
                (o = M.util.get_string(r, "assignrecertfeedback_editpdf")),
                  (a = this.get("iconprefix") + r),
                  (u = M.util.image_url(a, "assignrecertfeedback_editpdf")),
                  (i = e.Node.create(
                    '<button><img alt="' + o + '" src="' + u + '"/></button>'
                  )),
                  i.setAttribute("data-colour", r),
                  i.setAttribute("data-rgb", t),
                  i.setStyle("backgroundImage", "none"),
                  (s = e.Node.create("<li/>")),
                  s.append(i),
                  n.append(s);
              },
              this
            ),
              (r = e.Node.create("<div/>")),
              n.delegate("click", this.callback_handler, "button", this),
              n.delegate(
                "key",
                this.callback_handler,
                "down:13",
                "button",
                this
              ),
              this.set(
                "headerText",
                M.util.get_string(
                  "colourpicker",
                  "assignrecertfeedback_editpdf"
                )
              ),
              r.append(n),
              this.set("bodyContent", r),
              L.superclass.initializer.call(this, t);
          },
          callback_handler: function (t) {
            t.preventDefault();
            var n = this.get("callback"),
              r = this.get("context"),
              i;
            this.hide(), (i = e.bind(n, r, t)), i();
          },
        },
        {
          NAME: k,
          ATTRS: {
            colours: { value: {} },
            callback: { value: null },
            context: { value: null },
            iconprefix: { value: "colour_" },
          },
        }
      ),
      (M.assignrecertfeedback_editpdf = M.assignrecertfeedback_editpdf || {}),
      (M.assignrecertfeedback_editpdf.colourpicker = L);
    var A = "Colourpicker",
      O;
    (O = function (e) {
      O.superclass.constructor.apply(this, [e]);
    }),
      e.extend(
        O,
        M.assignrecertfeedback_editpdf.dropdown,
        {
          initializer: function (t) {
            var n = e.Node.create(
              '<ul role="menu" class="assignrecertfeedback_editpdf_menu"/>'
            );
            e.each(
              this.get("stamps"),
              function (t) {
                var r, i, s;
                (s = M.util.get_string(
                  "stamp",
                  "assignrecertfeedback_editpdf"
                )),
                  (r = e.Node.create(
                    '<button><img height="16" width="16" alt="' +
                      s +
                      '" src="' +
                      t +
                      '"/></button>'
                  )),
                  r.setAttribute("data-stamp", t),
                  r.setStyle("backgroundImage", "none"),
                  (i = e.Node.create("<li/>")),
                  i.append(r),
                  n.append(i);
              },
              this
            ),
              n.delegate("click", this.callback_handler, "button", this),
              n.delegate(
                "key",
                this.callback_handler,
                "down:13",
                "button",
                this
              ),
              this.set(
                "headerText",
                M.util.get_string("stamppicker", "assignrecertfeedback_editpdf")
              ),
              this.set("bodyContent", n),
              O.superclass.initializer.call(this, t);
          },
          callback_handler: function (t) {
            t.preventDefault();
            var n = this.get("callback"),
              r = this.get("context"),
              i;
            this.hide(), (i = e.bind(n, r, t)), i();
          },
        },
        {
          NAME: A,
          ATTRS: {
            stamps: { value: [] },
            callback: { value: null },
            context: { value: null },
          },
        }
      ),
      (M.assignrecertfeedback_editpdf = M.assignrecertfeedback_editpdf || {}),
      (M.assignrecertfeedback_editpdf.stamppicker = O);
    var _ = "Commentmenu",
      D;
    (D = function (e) {
      D.superclass.constructor.apply(this, [e]);
    }),
      e.extend(
        D,
        M.assignrecertfeedback_editpdf.dropdown,
        {
          initializer: function (t) {
            var n, r, i, s;
            (s = this.get("comment")),
              (n = e.Node.create(
                '<ul role="menu" class="assignrecertfeedback_editpdf_menu"/>'
              )),
              (r = e.Node.create(
                '<li><a tabindex="-1" href="#">' +
                  M.util.get_string(
                    "addtoquicklist",
                    "assignrecertfeedback_editpdf"
                  ) +
                  "</a></li>"
              )),
              r.on("click", s.add_to_quicklist, s),
              r.on("key", s.add_to_quicklist, "enter,space", s),
              n.append(r),
              (r = e.Node.create(
                '<li><a tabindex="-1" href="#">' +
                  M.util.get_string(
                    "deletecomment",
                    "assignrecertfeedback_editpdf"
                  ) +
                  "</a></li>"
              )),
              r.on(
                "click",
                function (e) {
                  e.preventDefault(), this.menu.hide(), this.remove();
                },
                s
              ),
              r.on(
                "key",
                function () {
                  s.menu.hide(), s.remove();
                },
                "enter,space",
                s
              ),
              n.append(r),
              (r = e.Node.create("<li><hr/></li>")),
              n.append(r),
              this.set(
                "headerText",
                M.util.get_string(
                  "commentcontextmenu",
                  "assignrecertfeedback_editpdf"
                )
              ),
              (i = e.Node.create("<div/>")),
              i.append(n),
              this.set("bodyContent", i),
              D.superclass.initializer.call(this, t);
          },
          show: function () {
            var t = this.get("boundingBox").one("ul");
            t.all(".quicklist_comment").remove(!0);
            var n = this.get("comment");
            (n.deleteme = !1),
              e.each(
                n.editor.quicklist.comments,
                function (r) {
                  var i = e.Node.create('<li class="quicklist_comment"></li>'),
                    s = e.Node.create(
                      '<a href="#" tabindex="-1">' + r.rawtext + "</a>"
                    ),
                    o = e.Node.create(
                      '<a href="#" tabindex="-1" class="delete_quicklist_comment"><img src="' +
                        M.util.image_url("t/delete", "core") +
                        '" ' +
                        'alt="' +
                        M.util.get_string(
                          "deletecomment",
                          "assignrecertfeedback_editpdf"
                        ) +
                        '"/>' +
                        "</a>"
                    );
                  i.append(s),
                    i.append(o),
                    t.append(i),
                    s.on("click", n.set_from_quick_comment, n, r),
                    s.on("key", n.set_from_quick_comment, "space,enter", n, r),
                    o.on("click", n.remove_from_quicklist, n, r),
                    o.on("key", n.remove_from_quicklist, "space,enter", n, r);
                },
                this
              ),
              D.superclass.show.call(this);
          },
        },
        { NAME: _, ATTRS: { comment: { value: null } } }
      ),
      (M.assignrecertfeedback_editpdf = M.assignrecertfeedback_editpdf || {}),
      (M.assignrecertfeedback_editpdf.commentmenu = D);
    var P = "commentsearch",
      H;
    (H = function (e) {
      (e.draggable = !1),
        (e.centered = !0),
        (e.width = "400px"),
        (e.visible = !1),
        (e.headerContent = M.util.get_string(
          "searchcomments",
          "assignrecertfeedback_editpdf"
        )),
        (e.footerContent = ""),
        H.superclass.constructor.apply(this, [e]);
    }),
      e.extend(
        H,
        M.core.dialogue,
        {
          initializer: function (t) {
            var n, r, i, s, o, u;
            (u = this.get("boundingBox")),
              u.addClass("assignrecertfeedback_editpdf_commentsearch"),
              (n = this.get("editor")),
              (r = e.Node.create("<div/>")),
              (i = M.util.get_string("filter", "assignrecertfeedback_editpdf")),
              (s = e.Node.create(
                '<input type="text" size="20" placeholder="' + i + '"/>'
              )),
              r.append(s),
              (o = e.Node.create(
                '<ul role="menu" class="assignrecertfeedback_editpdf_menu"/>'
              )),
              r.append(o),
              s.on("keyup", this.filter_search_comments, this),
              o.delegate("click", this.focus_on_comment, "a", this),
              o.delegate(
                "key",
                this.focus_on_comment,
                "enter,space",
                "a",
                this
              ),
              this.set("bodyContent", r),
              H.superclass.initializer.call(this, t);
          },
          filter_search_comments: function () {
            var t, n, r, i;
            (i = this.get("id")),
              (t = e.one("#" + i + s.SEARCHFILTER)),
              (n = e.one("#" + i + s.SEARCHCOMMENTSLIST)),
              (r = t.get("value")),
              n.all("li").each(function (e) {
                e.get("text").indexOf(r) !== -1 ? e.show() : e.hide();
              });
          },
          focus_on_comment: function (e) {
            e.preventDefault();
            var t = e.target.ancestor("li"),
              n = t.getData("comment"),
              r = this.get("editor"),
              i = r.get_dialogue_element(s.PAGESELECT);
            this.hide(),
              (r.currentpage = parseInt(i.get("value"), 10)),
              n.pageno !== r.currentpage &&
                ((r.currentpage = n.pageno), r.change_page()),
              n.drawable.nodes[0].one("textarea").focus();
          },
          show: function () {
            var t = this.get("boundingBox").one("ul"),
              n = this.get("editor");
            t.all("li").remove(!0),
              e.each(
                n.pages,
                function (n) {
                  e.each(
                    n.comments,
                    function (n) {
                      var r = e.Node.create(
                        '<li><a href="#" tabindex="-1"><pre>' +
                          n.rawtext +
                          "</pre></a></li>"
                      );
                      t.append(r), r.setData("comment", n);
                    },
                    this
                  );
                },
                this
              ),
              this.centerDialogue(),
              H.superclass.show.call(this);
          },
        },
        { NAME: P, ATTRS: { editor: { value: null } } }
      ),
      e.Base.modifyAttrs(H, {
        modal: {
          getter: function () {
            return !0;
          },
        },
      }),
      (M.assignrecertfeedback_editpdf = M.assignrecertfeedback_editpdf || {}),
      (M.assignrecertfeedback_editpdf.commentsearch = H);
    var B = function (t, n, r, i, o, u, l, c) {
      (this.editor = t),
        (this.gradeid = n || 0),
        (this.x = parseInt(i, 10) || 0),
        (this.y = parseInt(o, 10) || 0),
        (this.width = parseInt(u, 10) || 0),
        (this.rawtext = c || ""),
        (this.pageno = r || 0),
        (this.colour = l || "yellow"),
        (this.drawable = !1),
        (this.deleteme = !1),
        (this.menulink = null),
        (this.menu = null),
        (this.clean = function () {
          return {
            gradeid: this.gradeid,
            x: parseInt(this.x, 10),
            y: parseInt(this.y, 10),
            width: parseInt(this.width, 10),
            rawtext: this.rawtext,
            pageno: this.currentpage,
            colour: this.colour,
          };
        }),
        (this.draw = function (t) {
          var n = new M.assignrecertfeedback_editpdf.drawable(this.editor),
            r,
            i = this.editor.get_dialogue_element(s.DRAWINGCANVAS),
            o,
            u,
            l,
            c;
          return (
            (r = e.Node.create("<textarea/>")),
            (o = e.Node.create('<div class="commentdrawable"/>')),
            (u = e.Node.create(
              '<a href="#"><img src="' +
                M.util.image_url("t/contextmenu", "core") +
                '"/></a>'
            )),
            (this.menulink = u),
            o.append(r),
            this.editor.get("readonly")
              ? r.setAttribute("readonly", "readonly")
              : o.append(u),
            this.width < 100 && (this.width = 100),
            (l = this.editor.get_window_coordinates(
              new M.assignrecertfeedback_editpdf.point(this.x, this.y)
            )),
            r.setStyles({
              width: this.width + "px",
              backgroundColor: f[this.colour],
              color: a,
            }),
            i.append(o),
            o.setStyle("position", "absolute"),
            o.setX(l.x),
            o.setY(l.y),
            n.store_position(o, l.x, l.y),
            n.nodes.push(o),
            r.set("value", this.rawtext),
            (c = r.get("scrollHeight")),
            r.setStyles({ height: c + "px", overflow: "hidden" }),
            this.editor.get("readonly") || this.attach_events(r, u),
            t && r.focus(),
            (this.drawable = n),
            n
          );
        }),
        (this.delete_comment_later = function () {
          this.deleteme && this.remove();
        }),
        (this.attach_events = function (n, r) {
          n.on(
            "blur",
            function () {
              (this.rawtext = n.get("value")),
                (this.width = parseInt(n.getStyle("width"), 10)),
                this.rawtext.replace(/^\s+|\s+$/g, "") === "" &&
                  ((this.deleteme = !0),
                  e.later(400, this, this.delete_comment_later)),
                this.editor.save_current_page(),
                (this.editor.editingcomment = !1);
            },
            this
          ),
            r.setData("comment", this),
            n.on("keyup", function () {
              n.setStyle("height", "auto");
              var e = n.get("scrollHeight"),
                t = parseInt(n.getStyle("height"), 10);
              e === t + 8 && (e -= 8), n.setStyle("height", e + "px");
            }),
            n.on("gesturemovestart", function (e) {
              t.currentedit.tool === "select" &&
                (e.preventDefault(),
                n.setData("dragging", !0),
                n.setData("offsetx", e.clientX - n.getX()),
                n.setData("offsety", e.clientY - n.getY()));
            }),
            n.on(
              "gesturemoveend",
              function () {
                t.currentedit.tool === "select" &&
                  (n.setData("dragging", !1), this.editor.save_current_page());
              },
              null,
              this
            ),
            n.on(
              "gesturemove",
              function (e) {
                if (t.currentedit.tool === "select") {
                  var r = e.clientX - n.getData("offsetx"),
                    i = e.clientY - n.getData("offsety"),
                    s,
                    o,
                    u,
                    a,
                    f;
                  (s = parseInt(n.getStyle("width"), 10)),
                    (o = parseInt(n.getStyle("height"), 10)),
                    (u = this.editor.get_canvas_coordinates(
                      new M.assignrecertfeedback_editpdf.point(r, i)
                    )),
                    (f = this.editor.get_canvas_bounds(!0)),
                    (f.x = 0),
                    (f.y = 0),
                    (f.width -= s + 42),
                    (f.height -= o + 8),
                    u.clip(f),
                    (this.x = u.x),
                    (this.y = u.y),
                    (a = this.editor.get_window_coordinates(u)),
                    n.ancestor().setX(a.x),
                    n.ancestor().setY(a.y),
                    this.drawable.store_position(n.ancestor(), a.x, a.y);
                }
              },
              null,
              this
            ),
            (this.menu = new M.assignrecertfeedback_editpdf.commentmenu({
              buttonNode: this.menulink,
              comment: this,
            }));
        }),
        (this.remove = function () {
          var e = 0,
            t;
          t = this.editor.pages[this.editor.currentpage].comments;
          for (e = 0; e < t.length; e++)
            if (t[e] === this) {
              t.splice(e, 1),
                this.drawable.erase(),
                this.editor.save_current_page();
              return;
            }
        }),
        (this.remove_from_quicklist = function (e, t) {
          e.preventDefault(), this.menu.hide(), this.editor.quicklist.remove(t);
        }),
        (this.set_from_quick_comment = function (e, t) {
          e.preventDefault(),
            this.menu.hide(),
            (this.rawtext = t.rawtext),
            (this.width = t.width),
            (this.colour = t.colour),
            this.editor.save_current_page(),
            this.editor.redraw();
        }),
        (this.add_to_quicklist = function (e) {
          e.preventDefault(), this.menu.hide(), this.editor.quicklist.add(this);
        }),
        (this.draw_current_edit = function (t) {
          var n = new M.assignrecertfeedback_editpdf.drawable(this.editor),
            r,
            i;
          return (
            (i = new M.assignrecertfeedback_editpdf.rect()),
            i.bound([t.start, t.end]),
            (r = this.editor.graphic.addShape({
              type: e.Rect,
              width: i.width,
              height: i.height,
              fill: { color: f[t.commentcolour] },
              x: i.x,
              y: i.y,
            })),
            n.shapes.push(r),
            n
          );
        }),
        (this.init_from_edit = function (e) {
          var t = new M.assignrecertfeedback_editpdf.rect();
          return (
            t.bound([e.start, e.end]),
            t.width < 100 && (t.width = 100),
            (this.gradeid = this.editor.get("gradeid")),
            (this.pageno = this.editor.currentpage),
            (this.x = t.x),
            (this.y = t.y),
            (this.width = t.width),
            (this.colour = e.commentcolour),
            (this.rawtext = ""),
            t.has_min_width() && t.has_min_height()
          );
        });
    };
    (M.assignrecertfeedback_editpdf = M.assignrecertfeedback_editpdf || {}),
      (M.assignrecertfeedback_editpdf.comment = B);
    var j = function (e, t, n, r) {
      (this.rawtext = t || ""),
        (this.id = e || 0),
        (this.width = n || 100),
        (this.colour = r || "yellow");
    };
    (M.assignrecertfeedback_editpdf = M.assignrecertfeedback_editpdf || {}),
      (M.assignrecertfeedback_editpdf.quickcomment = j);
    var F = function (t) {
      (this.editor = t),
        (this.comments = []),
        (this.add = function (t) {
          var r = n,
            i;
          if (t.rawtext === "") return;
          (i = {
            method: "post",
            context: this,
            sync: !1,
            data: {
              sesskey: M.cfg.sesskey,
              action: "addtoquicklist",
              userid: this.editor.get("userid"),
              commenttext: t.rawtext,
              width: t.width,
              colour: t.colour,
              attemptnumber: this.editor.get("attemptnumber"),
              assignmentrecertid: this.editor.get("assignmentrecertid"),
            },
            on: {
              success: function (t, n) {
                var r, i;
                try {
                  r = e.JSON.parse(n.responseText);
                  if (r.error) return new M.core.ajaxException(r);
                  (i = new M.assignrecertfeedback_editpdf.quickcomment(
                    r.id,
                    r.rawtext,
                    r.width,
                    r.colour
                  )),
                    this.comments.push(i),
                    this.comments.sort(function (e, t) {
                      return e.rawtext.localeCompare(t.rawtext);
                    });
                } catch (s) {
                  return new M.core.exception(s);
                }
              },
              failure: function (e, t) {
                return M.core.exception(t.responseText);
              },
            },
          }),
            e.io(r, i);
        }),
        (this.remove = function (t) {
          var r = n,
            i;
          if (!t) return;
          (i = {
            method: "post",
            context: this,
            sync: !1,
            data: {
              sesskey: M.cfg.sesskey,
              action: "removefromquicklist",
              userid: this.editor.get("userid"),
              commentid: t.id,
              attemptnumber: this.editor.get("attemptnumber"),
              assignmentrecertid: this.editor.get("assignmentrecertid"),
            },
            on: {
              success: function () {
                var e;
                (e = this.comments.indexOf(t)),
                  e >= 0 && this.comments.splice(e, 1);
              },
              failure: function (e, t) {
                return M.core.exception(t.responseText);
              },
            },
          }),
            e.io(r, i);
        }),
        (this.load = function () {
          var t = n,
            r;
          (r = {
            method: "get",
            context: this,
            sync: !1,
            data: {
              sesskey: M.cfg.sesskey,
              action: "loadquicklist",
              userid: this.editor.get("userid"),
              attemptnumber: this.editor.get("attemptnumber"),
              assignmentrecertid: this.editor.get("assignmentrecertid"),
            },
            on: {
              success: function (t, n) {
                var r;
                try {
                  r = e.JSON.parse(n.responseText);
                  if (r.error) return new M.core.ajaxException(r);
                  e.each(
                    r,
                    function (e) {
                      var t = new M.assignrecertfeedback_editpdf.quickcomment(
                        e.id,
                        e.rawtext,
                        e.width,
                        e.colour
                      );
                      this.comments.push(t);
                    },
                    this
                  ),
                    this.comments.sort(function (e, t) {
                      return e.rawtext.localeCompare(t.rawtext);
                    });
                } catch (i) {
                  return new M.core.exception(i);
                }
              },
              failure: function (e, t) {
                return M.core.exception(t.responseText);
              },
            },
          }),
            e.io(t, r);
        });
    };
    (M.assignrecertfeedback_editpdf = M.assignrecertfeedback_editpdf || {}),
      (M.assignrecertfeedback_editpdf.quickcommentlist = F);
    var I = function () {
      I.superclass.constructor.apply(this, arguments);
    };
    (I.prototype = {
      dialogue: null,
      panel: null,
      pagecount: 0,
      currentpage: 0,
      pages: [],
      loadingicon: null,
      pageimage: null,
      graphic: null,
      currentedit: new M.assignrecertfeedback_editpdf.edit(),
      currentdrawable: !1,
      drawables: [],
      currentcomment: null,
      currentannotation: null,
      lastannotation: null,
      lastannotationtool: "pen",
      quicklist: null,
      searchcommentswindow: null,
      currentstamp: null,
      stamps: [],
      editingcomment: !1,
      initializer: function () {
        var t;
        (t = e.one("#" + this.get("linkid"))),
          t &&
            (t.on("click", this.link_handler, this),
            t.on("key", this.link_handler, "down:13", this),
            require(["mod_assignrecert/grading_review_panel"], function (n) {
              var r = new n(),
                i = r.getReviewPanel("assignrecertfeedback_editpdf");
              i &&
                ((i = e.one(i)),
                i.empty(),
                t.ancestor(".fitem").hide(),
                this.open_in_panel(i)),
                (this.currentedit.start = !1),
                (this.currentedit.end = !1),
                this.get("readonly") ||
                  (this.quicklist =
                    new M.assignrecertfeedback_editpdf.quickcommentlist(this));
            }.bind(this)));
      },
      refresh_button_state: function () {
        var e, t, n, r;
        (e = this.get_dialogue_element(s.COMMENTCOLOURBUTTON)),
          (n = M.util.image_url(
            "background_colour_" + this.currentedit.commentcolour,
            "assignrecertfeedback_editpdf"
          )),
          e.one("img").setAttribute("src", n),
          this.currentedit.commentcolour === "clear"
            ? e.one("img").setStyle("borderStyle", "dashed")
            : e.one("img").setStyle("borderStyle", "solid"),
          (e = this.get_dialogue_element(s.ANNOTATIONCOLOURBUTTON)),
          (n = M.util.image_url(
            "colour_" + this.currentedit.annotationcolour,
            "assignrecertfeedback_editpdf"
          )),
          e.one("img").setAttribute("src", n),
          (t = this.get_dialogue_element(h[this.currentedit.tool])),
          t.addClass("assignrecertfeedback_editpdf_selectedbutton"),
          t.setAttribute("aria-pressed", "true"),
          (r = this.get_dialogue_element(s.DRAWINGREGION)),
          r.setAttribute("data-currenttool", this.currentedit.tool),
          (e = this.get_dialogue_element(s.STAMPSBUTTON)),
          e.one("img").setAttrs({
            src: this.get_stamp_image_url(this.currentedit.stamp),
            height: "16",
            width: "16",
          });
      },
      get_canvas_bounds: function () {
        var e = this.get_dialogue_element(s.DRAWINGCANVAS),
          t = e.getXY(),
          n = t[0],
          r = t[1],
          i = parseInt(e.getStyle("width"), 10),
          o = parseInt(e.getStyle("height"), 10);
        return new M.assignrecertfeedback_editpdf.rect(n, r, i, o);
      },
      get_canvas_coordinates: function (e) {
        var t = this.get_canvas_bounds(),
          n = new M.assignrecertfeedback_editpdf.point(e.x - t.x, e.y - t.y);
        return (t.x = t.y = 0), n.clip(t), n;
      },
      get_window_coordinates: function (e) {
        var t = this.get_canvas_bounds(),
          n = new M.assignrecertfeedback_editpdf.point(e.x + t.x, e.y + t.y);
        return n;
      },
      open_in_panel: function (t) {
        var n;
        (this.panel = t),
          t.append(this.get("body")),
          t.addClass(i.DIALOGUE),
          (this.loadingicon = this.get_dialogue_element(s.LOADINGICON)),
          (n = this.get_dialogue_element(s.DRAWINGCANVAS)),
          (this.graphic = new e.Graphic({ render: n })),
          this.get("readonly") ||
            (n.on("gesturemovestart", this.edit_start, null, this),
            n.on("gesturemove", this.edit_move, null, this),
            n.on("gesturemoveend", this.edit_end, null, this),
            this.refresh_button_state()),
          this.load_all_pages();
      },
      link_handler: function (t) {
        var n,
          r = !0;
        t.preventDefault(),
          this.dialogue ||
            ((this.dialogue = new M.core.dialogue({
              headerContent: this.get("header"),
              bodyContent: this.get("body"),
              footerContent: this.get("footer"),
              modal: !0,
              width: "840px",
              visible: !1,
              draggable: !0,
            })),
            this.dialogue.get("boundingBox").addClass(i.DIALOGUE),
            (this.loadingicon = this.get_dialogue_element(s.LOADINGICON)),
            (n = this.get_dialogue_element(s.DRAWINGCANVAS)),
            (this.graphic = new e.Graphic({ render: n })),
            this.get("readonly") ||
              (n.on("gesturemovestart", this.edit_start, null, this),
              n.on("gesturemove", this.edit_move, null, this),
              n.on("gesturemoveend", this.edit_end, null, this),
              this.refresh_button_state()),
            this.load_all_pages(),
            n.on("windowresize", this.resize, this),
            (r = !1)),
          this.dialogue.centerDialogue(),
          this.dialogue.show(),
          this.dialogue.dd.on("drag:end", this.redraw, this),
          r && this.resize();
      },
      load_all_pages: function () {
        var t = n,
          i,
          o,
          u;
        (i = {
          method: "get",
          context: this,
          sync: !1,
          data: {
            sesskey: M.cfg.sesskey,
            action: "loadallpages",
            userid: this.get("userid"),
            attemptnumber: this.get("attemptnumber"),
            assignmentrecertid: this.get("assignmentrecertid"),
            readonly: this.get("readonly") ? 1 : 0,
          },
          on: {
            success: function (e, t) {
              this.all_pages_loaded(t.responseText);
            },
            failure: function (e, t) {
              return new M.core.exception(t.responseText);
            },
          },
        }),
          e.io(t, i),
          this.pagecount <= 0 &&
            ((o = {
              method: "get",
              context: this,
              sync: !1,
              data: {
                sesskey: M.cfg.sesskey,
                action: "conversionstatus",
                userid: this.get("userid"),
                attemptnumber: this.get("attemptnumber"),
                assignmentrecertid: this.get("assignmentrecertid"),
              },
              on: {
                success: function (t, n) {
                  u = 0;
                  if (this.pagecount === 0) {
                    var i = this.get("pagetotal"),
                      a = this.get_dialogue_element(s.PROGRESSBARCONTAINER),
                      f = a.one(".bar");
                    if (f) {
                      var l = (n.response / i) * 100;
                      f.setStyle("width", l + "%"),
                        a.setAttribute("aria-valuenow", l);
                    }
                    M.util.js_pending("checkconversionstatus"),
                      e.later(1e3, this, function () {
                        M.util.js_complete("checkconversionstatus"), e.io(r, o);
                      });
                  }
                },
                failure: function (t, n) {
                  return (
                    (u += 1),
                    this.pagecount === 0 &&
                      u < 5 &&
                      (M.util.js_pending("checkconversionstatus"),
                      e.later(1e3, this, function () {
                        M.util.js_complete("checkconversionstatus"), e.io(r, o);
                      })),
                    new M.core.exception(n.responseText)
                  );
                },
              },
            }),
            M.util.js_pending("checkconversionstatus"),
            e.later(1e3, this, function () {
              (u = 0), M.util.js_complete("checkconversionstatus"), e.io(r, o);
            }));
      },
      all_pages_loaded: function (t) {
        var n, r, i, s, o;
        try {
          n = e.JSON.parse(t);
          if (n.error || !n.pagecount) {
            this.dialogue && this.dialogue.hide(),
              (o = new M.core.alert({
                message: M.util.get_string(
                  "cannotopenpdf",
                  "assignrecertfeedback_editpdf"
                ),
              })),
              o.show();
            return;
          }
        } catch (u) {
          this.dialogue && this.dialogue.hide(),
            (o = new M.core.alert({
              title: M.util.get_string(
                "cannotopenpdf",
                "assignrecertfeedback_editpdf"
              ),
            })),
            o.show();
          return;
        }
        (this.pagecount = n.pagecount), (this.pages = n.pages);
        for (r = 0; r < this.pages.length; r++) {
          for (i = 0; i < this.pages[r].comments.length; i++)
            (s = this.pages[r].comments[i]),
              (this.pages[r].comments[i] =
                new M.assignrecertfeedback_editpdf.comment(
                  this,
                  s.gradeid,
                  s.pageno,
                  s.x,
                  s.y,
                  s.width,
                  s.colour,
                  s.rawtext
                ));
          for (i = 0; i < this.pages[r].annotations.length; i++)
            (n = this.pages[r].annotations[i]),
              (this.pages[r].annotations[i] = this.create_annotation(
                n.type,
                n
              ));
        }
        this.quicklist && this.quicklist.load(),
          this.setup_navigation(),
          this.setup_toolbar(),
          this.change_page();
      },
      get_stamp_image_url: function (t) {
        var n = this.get("stampfiles"),
          r = "";
        return (
          e.Array.each(
            n,
            function (e) {
              e.indexOf(t) > 0 && (r = e);
            },
            this
          ),
          r
        );
      },
      setup_toolbar: function () {
        var t, n, r, i, o, u, a, c;
        (i = this.get_dialogue_element(s.SEARCHCOMMENTSBUTTON)),
          i.on("click", this.open_search_comments, this),
          i.on("key", this.open_search_comments, "down:13", this);
        if (this.get("readonly")) return;
        e.each(
          h,
          function (e, n) {
            (t = this.get_dialogue_element(e)),
              t.on("click", this.handle_tool_button, this, n),
              t.on("key", this.handle_tool_button, "down:13", this, n),
              t.setAttribute("aria-pressed", "false");
          },
          this
        ),
          (n = this.get_dialogue_element(s.COMMENTCOLOURBUTTON)),
          (a = new M.assignrecertfeedback_editpdf.colourpicker({
            buttonNode: n,
            colours: f,
            iconprefix: "background_colour_",
            callback: function (e) {
              var t = e.target.getAttribute("data-colour");
              t || (t = e.target.ancestor().getAttribute("data-colour")),
                (this.currentedit.commentcolour = t),
                this.handle_tool_button(e, "comment");
            },
            context: this,
          })),
          (r = this.get_dialogue_element(s.ANNOTATIONCOLOURBUTTON)),
          (a = new M.assignrecertfeedback_editpdf.colourpicker({
            buttonNode: r,
            iconprefix: "colour_",
            colours: l,
            callback: function (e) {
              var t = e.target.getAttribute("data-colour");
              t || (t = e.target.ancestor().getAttribute("data-colour")),
                (this.currentedit.annotationcolour = t),
                this.lastannotationtool
                  ? this.handle_tool_button(e, this.lastannotationtool)
                  : this.handle_tool_button(e, "pen");
            },
            context: this,
          })),
          (u = this.get("stampfiles")),
          u.length <= 0
            ? this.get_dialogue_element(h.stamp).ancestor().hide()
            : ((c = u[0].substr(u[0].lastIndexOf("/") + 1)),
              (this.currentedit.stamp = c),
              (o = this.get_dialogue_element(s.STAMPSBUTTON)),
              (a = new M.assignrecertfeedback_editpdf.stamppicker({
                buttonNode: o,
                stamps: u,
                callback: function (e) {
                  var t = e.target.getAttribute("data-stamp"),
                    n;
                  t || (t = e.target.ancestor().getAttribute("data-stamp")),
                    (n = t.substr(t.lastIndexOf("/"))),
                    (this.currentedit.stamp = n),
                    this.handle_tool_button(e, "stamp");
                },
                context: this,
              })),
              this.refresh_button_state());
      },
      handle_tool_button: function (e, t) {
        var n;
        e.preventDefault(),
          (n = this.get_dialogue_element(h[this.currentedit.tool])),
          n.removeClass("assignrecertfeedback_editpdf_selectedbutton"),
          n.setAttribute("aria-pressed", "false"),
          (this.currentedit.tool = t),
          t !== "comment" &&
            t !== "select" &&
            t !== "drag" &&
            t !== "stamp" &&
            (this.lastannotationtool = t),
          this.refresh_button_state();
      },
      stringify_current_page: function () {
        var t = [],
          n = [],
          r,
          i = 0;
        for (i = 0; i < this.pages[this.currentpage].comments.length; i++)
          t[i] = this.pages[this.currentpage].comments[i].clean();
        for (i = 0; i < this.pages[this.currentpage].annotations.length; i++)
          n[i] = this.pages[this.currentpage].annotations[i].clean();
        return (r = { comments: t, annotations: n }), e.JSON.stringify(r);
      },
      get_current_drawable: function () {
        var e,
          t,
          n = !1;
        return !this.currentedit.start || !this.currentedit.end
          ? !1
          : (this.currentedit.tool === "comment"
              ? ((e = new M.assignrecertfeedback_editpdf.comment(this)),
                (n = e.draw_current_edit(this.currentedit)))
              : ((t = this.create_annotation(this.currentedit.tool, {})),
                t && (n = t.draw_current_edit(this.currentedit))),
            n);
      },
      get_dialogue_element: function (e) {
        return this.panel
          ? this.panel.one(e)
          : this.dialogue.get("boundingBox").one(e);
      },
      redraw_current_edit: function () {
        this.currentdrawable && this.currentdrawable.erase(),
          (this.currentdrawable = this.get_current_drawable());
      },
      edit_start: function (t) {
        t.preventDefault();
        var n = this.get_dialogue_element(s.DRAWINGCANVAS),
          r = n.getXY(),
          i = n.get("docScrollY"),
          o = n.get("docScrollX"),
          u = { x: t.clientX - r[0] + o, y: t.clientY - r[1] + i },
          a = !1;
        if (t.button === 3) return;
        if (this.currentedit.starttime) return;
        if (this.editingcomment) return;
        (this.currentedit.starttime = new Date().getTime()),
          (this.currentedit.start = u),
          (this.currentedit.end = { x: u.x, y: u.y });
        if (this.currentedit.tool === "select") {
          var f = this.currentedit.end.x,
            l = this.currentedit.end.y,
            c = this.pages[this.currentpage].annotations;
          e.each(c, function (e) {
            (f - e.x) * (f - e.endx) <= 0 &&
              (l - e.y) * (l - e.endy) <= 0 &&
              (a = e);
          }),
            a
              ? ((this.lastannotation = this.currentannotation),
                (this.currentannotation = a),
                this.lastannotation &&
                  this.lastannotation !== a &&
                  this.lastannotation.drawable &&
                  (this.lastannotation.drawable.erase(),
                  this.drawables.push(this.lastannotation.draw())),
                this.currentannotation.drawable &&
                  this.currentannotation.drawable.erase(),
                this.drawables.push(this.currentannotation.draw()))
              : ((this.lastannotation = this.currentannotation),
                (this.currentannotation = null),
                this.lastannotation &&
                  this.lastannotation.drawable &&
                  (this.lastannotation.drawable.erase(),
                  this.drawables.push(this.lastannotation.draw())));
        }
        this.currentannotation &&
          (this.currentedit.annotationstart = {
            x: this.currentannotation.x,
            y: this.currentannotation.y,
          });
      },
      edit_move: function (e) {
        e.preventDefault();
        var t = this.get_canvas_bounds(),
          n = this.get_dialogue_element(s.DRAWINGCANVAS),
          r = this.get_dialogue_element(s.DRAWINGREGION),
          i = new M.assignrecertfeedback_editpdf.point(
            e.clientX + n.get("docScrollX"),
            e.clientY + n.get("docScrollY")
          ),
          o = this.get_canvas_coordinates(i),
          u,
          a;
        if (o.x < 0 || o.x > t.width || o.y < 0 || o.y > t.height) return;
        this.currentedit.tool === "pen" && this.currentedit.path.push(o),
          this.currentedit.tool === "select"
            ? this.currentannotation &&
              this.currentedit &&
              this.currentannotation.move(
                this.currentedit.annotationstart.x +
                  o.x -
                  this.currentedit.start.x,
                this.currentedit.annotationstart.y +
                  o.y -
                  this.currentedit.start.y
              )
            : this.currentedit.tool === "drag"
            ? ((u = o.x - this.currentedit.start.x),
              (a = o.y - this.currentedit.start.y),
              (r.getDOMNode().scrollLeft -= u),
              (r.getDOMNode().scrollTop -= a))
            : this.currentedit.start &&
              ((this.currentedit.end = o), this.redraw_current_edit());
      },
      edit_end: function () {
        var e, t, n;
        e = new Date().getTime() - this.currentedit.start;
        if (e < c || this.currentedit.start === !1) return;
        this.currentedit.tool === "comment"
          ? (this.currentdrawable && this.currentdrawable.erase(),
            (this.currentdrawable = !1),
            (t = new M.assignrecertfeedback_editpdf.comment(this)),
            t.init_from_edit(this.currentedit) &&
              (this.pages[this.currentpage].comments.push(t),
              this.drawables.push(t.draw(!0)),
              (this.editingcomment = !0)))
          : ((n = this.create_annotation(this.currentedit.tool, {})),
            n &&
              (this.currentdrawable && this.currentdrawable.erase(),
              (this.currentdrawable = !1),
              n.init_from_edit(this.currentedit) &&
                (this.pages[this.currentpage].annotations.push(n),
                this.drawables.push(n.draw())))),
          this.save_current_page(),
          (this.currentedit.starttime = 0),
          (this.currentedit.start = !1),
          (this.currentedit.end = !1),
          (this.currentedit.path = []);
      },
      resize: function () {
        var t, n;
        if (this.dialogue) {
          if (!this.dialogue.get("visible")) return;
          this.dialogue.centerDialogue();
        }
        return (
          (n = e.one("body").get("winHeight") - 120),
          n < 100 && (n = 100),
          (t = this.get_dialogue_element(s.DRAWINGREGION)),
          this.dialogue && t.setStyle("maxHeight", n + "px"),
          this.redraw(),
          !0
        );
      },
      create_annotation: function (e, t) {
        return (
          (t.type = e),
          (t.editor = this),
          e === "line"
            ? new M.assignrecertfeedback_editpdf.annotationline(t)
            : e === "rectangle"
            ? new M.assignrecertfeedback_editpdf.annotationrectangle(t)
            : e === "oval"
            ? new M.assignrecertfeedback_editpdf.annotationoval(t)
            : e === "pen"
            ? new M.assignrecertfeedback_editpdf.annotationpen(t)
            : e === "highlight"
            ? new M.assignrecertfeedback_editpdf.annotationhighlight(t)
            : e === "stamp"
            ? new M.assignrecertfeedback_editpdf.annotationstamp(t)
            : !1
        );
      },
      save_current_page: function () {
        var t = n,
          r = this.get_dialogue_element(s.PAGESELECT),
          i;
        (this.currentpage = parseInt(r.get("value"), 10)),
          (i = {
            method: "post",
            context: this,
            sync: !1,
            data: {
              sesskey: M.cfg.sesskey,
              action: "savepage",
              index: this.currentpage,
              userid: this.get("userid"),
              attemptnumber: this.get("attemptnumber"),
              assignmentrecertid: this.get("assignmentrecertid"),
              page: this.stringify_current_page(),
            },
            on: {
              success: function (t, n) {
                var r;
                try {
                  r = e.JSON.parse(n.responseText);
                  if (r.error) return new M.core.ajaxException(r);
                  e.one(s.UNSAVEDCHANGESINPUT).set("value", "true"),
                    e.one(s.UNSAVEDCHANGESDIV).setStyle("opacity", 1),
                    e
                      .one(s.UNSAVEDCHANGESDIV)
                      .setStyle("display", "inline-block"),
                    e
                      .one(s.UNSAVEDCHANGESDIV)
                      .transition(
                        { duration: 1, delay: 2, opacity: 0 },
                        function () {
                          e.one(s.UNSAVEDCHANGESDIV).setStyle(
                            "display",
                            "none"
                          );
                        }
                      );
                } catch (i) {
                  return new M.core.exception(i);
                }
              },
              failure: function (e, t) {
                return new M.core.exception(t.responseText);
              },
            },
          }),
          e.io(t, i);
      },
      open_search_comments: function (e) {
        this.searchcommentswindow ||
          (this.searchcommentswindow =
            new M.assignrecertfeedback_editpdf.commentsearch({ editor: this })),
          this.searchcommentswindow.show(),
          e.preventDefault();
      },
      redraw: function () {
        var e, t;
        t = this.pages[this.currentpage];
        if (t === undefined) return;
        while (this.drawables.length > 0) this.drawables.pop().erase();
        for (e = 0; e < t.annotations.length; e++)
          this.drawables.push(t.annotations[e].draw());
        for (e = 0; e < t.comments.length; e++)
          this.drawables.push(t.comments[e].draw(!1));
      },
      change_page: function () {
        var e = this.get_dialogue_element(s.DRAWINGCANVAS),
          t,
          n,
          r;
        (n = this.get_dialogue_element(s.PREVIOUSBUTTON)),
          (r = this.get_dialogue_element(s.NEXTBUTTON)),
          this.currentpage > 0
            ? n.removeAttribute("disabled")
            : n.setAttribute("disabled", "true"),
          this.currentpage < this.pagecount - 1
            ? r.removeAttribute("disabled")
            : r.setAttribute("disabled", "true"),
          (t = this.pages[this.currentpage]),
          this.loadingicon.hide(),
          e.setStyle("backgroundImage", 'url("' + t.url + '")'),
          e.setStyle("width", t.width + "px"),
          e.setStyle("height", t.height + "px"),
          this.get_dialogue_element(s.PAGESELECT).set(
            "selectedIndex",
            this.currentpage
          ),
          this.resize();
      },
      setup_navigation: function () {
        var t, n, r, i, o, u;
        t = this.get_dialogue_element(s.PAGESELECT);
        var a = t.all("option");
        if (a.size() <= 1)
          for (n = 0; n < this.pages.length; n++)
            (i = e.Node.create("<option/>")),
              i.setAttribute("value", n),
              (r = { page: n + 1, total: this.pages.length }),
              i.setHTML(
                M.util.get_string("pagexofy", "assignrecertfeedback_editpdf", r)
              ),
              t.append(i);
        t.removeAttribute("disabled"),
          t.on(
            "change",
            function () {
              (this.currentpage = t.get("value")), this.change_page();
            },
            this
          ),
          (o = this.get_dialogue_element(s.PREVIOUSBUTTON)),
          (u = this.get_dialogue_element(s.NEXTBUTTON)),
          o.on("click", this.previous_page, this),
          o.on("key", this.previous_page, "down:13", this),
          u.on("click", this.next_page, this),
          u.on("key", this.next_page, "down:13", this);
      },
      previous_page: function (e) {
        e.preventDefault();
        var t = this.get_dialogue_element(s.PAGESELECT);
        (this.currentpage = parseInt(t.get("value"), 10) - 1),
          this.currentpage < 0 && (this.currentpage = 0),
          this.change_page();
      },
      next_page: function (e) {
        e.preventDefault();
        var t = this.get_dialogue_element(s.PAGESELECT);
        (this.currentpage = parseInt(t.get("value"), 10) + 1),
          this.currentpage >= this.pages.length &&
            (this.currentpage = this.pages.length - 1),
          this.change_page();
      },
      move_canvas: function () {
        var e, t, n, r;
        (e = this.get_dialogue_element(s.DRAWINGREGION)),
          (t = parseInt(e.get("scrollLeft"), 10)),
          (n = parseInt(e.get("scrollTop"), 10));
        for (r = 0; r < this.drawables.length; r++)
          this.drawables[r].scroll_update(t, n);
      },
    }),
      e.extend(I, e.Base, I.prototype, {
        NAME: "moodle-assignrecertfeedback_editpdf-editor",
        ATTRS: {
          userid: { validator: e.Lang.isInteger, value: 0 },
          assignmentrecertid: { validator: e.Lang.isInteger, value: 0 },
          attemptnumber: { validator: e.Lang.isInteger, value: 0 },
          header: { validator: e.Lang.isString, value: "" },
          body: { validator: e.Lang.isString, value: "" },
          footer: { validator: e.Lang.isString, value: "" },
          linkid: { validator: e.Lang.isString, value: "" },
          deletelinkid: { validator: e.Lang.isString, value: "" },
          readonly: { validator: e.Lang.isBoolean, value: !0 },
          stampfiles: { validator: e.Lang.isArray, value: "" },
          pagetotal: { validator: e.Lang.isInteger, value: 0 },
        },
      }),
      (M.assignrecertfeedback_editpdf = M.assignrecertfeedback_editpdf || {}),
      (M.assignrecertfeedback_editpdf.editor =
        M.assignrecertfeedback_editpdf.editor || {}),
      (M.assignrecertfeedback_editpdf.editor.init =
        M.assignrecertfeedback_editpdf.editor.init ||
        function (e) {
          return (
            (M.assignrecertfeedback_editpdf.instance = new I(e)),
            M.assignrecertfeedback_editpdf.instance
          );
        });
  },
  "@VERSION@",
  {
    requires: [
      "base",
      "event",
      "node",
      "io",
      "graphics",
      "json",
      "event-move",
      "event-resize",
      "transition",
      "querystring-stringify-simple",
      "moodle-core-notification-dialog",
      "moodle-core-notification-exception",
      "moodle-core-notification-ajaxexception",
    ],
  }
);
