
inc("protovis-r3.2.js");

/** A simple sparkline with optional dots. */
function sparkline(data, dots) {
    var n = data.length,
        w = n,
        h = 10,
        min = pv.min.index(data),
        max = pv.max.index(data);
    var vis = new pv.Panel()
        .width(w)
        .height(h)
        .margin(2);
    vis.add(pv.Line)
        .data(data)
        .left(pv.Scale.linear(0, n - 1).range(0, w).by(pv.index))
        .bottom(pv.Scale.linear(data).range(0, h))
        .strokeStyle("#000")
        .lineWidth(1)
        .add(pv.Dot)
        .visible(function() (dots && this.index == 0) || this.index == n - 1)
        .strokeStyle(null)
        .fillStyle("brown")
        .radius(2)
        .add(pv.Dot)
        .visible(function() dots && (this.index == min || this.index == max))
        .fillStyle("steelblue");
    vis.render();
}

/** Generates a random walk of length n. */
function walk(n) {
    var array = [], value = 0, i = 0;
    while (n-- > 0) {
        array.push(value += (Math.random() - .5));
    }
    return array;
}


