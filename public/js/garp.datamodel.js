/**
	@author David Spreekmeester | grrr.nl
*/

Datamodel = {
	models: models,
	
	Relations: {
		_ctx: null,
		
		_ctxOffset: null,
		
		_canvasEl: null,

		initCanvas: function() {
			var centerCorrectX = -145;
			var centerCorrectY = -90;
			var canvasJq = $('#canvas');
			this._canvasEl = canvasJq[0];
			this._canvasEl.width = $('#content').width();
			this._canvasEl.height = $('#content').height();
			this._ctxOffset = canvasJq.offset();
			this._ctxOffset.left = this._ctxOffset.left + centerCorrectX;
			this._ctxOffset.top = this._ctxOffset.top + centerCorrectY;
			this._ctx = this._canvasEl.getContext("2d");
			this.clearCanvas();
			this._ctx.lineWidth = 5;
			this._ctx.strokeStyle = "#ca7";	
		},
		
		clearCanvas: function() {
			this._ctx.clearRect(0, 0, this._canvasEl.width, this._canvasEl.height);
		},

		connectModel: function(model1, model2) {
			this._ctx.beginPath();

			var srcOffset = model1.offset();
			var trgOffset = model2.offset();
			var halfWidth = model1.width() / 2;
			var halfHeight = model1.height() / 2;

			var sourceX = srcOffset.left - this._ctxOffset.left - halfWidth;
			var sourceY = srcOffset.top - this._ctxOffset.top - halfHeight;
			var targetX = trgOffset.left - this._ctxOffset.left - halfWidth;
			var targetY = trgOffset.top - this._ctxOffset.top - halfHeight;

			// var midBetweenModelsX = Math.min(sourceX, targetX) + Math.abs(sourceX - targetX);
			// var midBetweenModelsY = Math.min(sourceY, targetY) + Math.abs(sourceY - targetY);
			var canvasMidX = this._canvasEl.width / 2;
			var canvasMidY = this._canvasEl.height / 2;

			var utmostRightModelX = Math.max(sourceX, targetX);
			var utmostLeftModelX = Math.min(sourceX, targetX);

			if (utmostRightModelX < canvasMidX) {
				var controlX = utmostRightModelX;
			} else if (utmostLeftModelX > canvasMidX) {
				var controlX = utmostLeftModelX;
			} else {
				var controlX = canvasMidX;
			}

			// var controlY = canvasMidY;

			var topModelY = Math.min(sourceY, targetY);
			var bottomModelY = Math.max(sourceY, targetY);
			var radiusY = halfHeight * 2;

			if (topModelY > canvasMidY) {
				var controlY = topModelY - radiusY;
			} else if (bottomModelY < canvasMidY) {
				var controlY = bottomModelY + radiusY;
			} else {
				var controlY = canvasMidY;
			}
			

			// var controlX = Math.min(midBetweenModelsX, canvasMidX) + Math.abs(midBetweenModelsX - canvasMidX);
			// var controlY = Math.min(midBetweenModelsY, canvasMidY) + Math.abs(midBetweenModelsY - canvasMidY);

			this._ctx.moveTo(sourceX, sourceY);
			// this._ctx.bezierCurveTo(100, 200, 150, 0, targetX, targetY);
			this._ctx.quadraticCurveTo(controlX, controlY, targetX, targetY);
			this._ctx.stroke();
			this._ctx.closePath();
		}		
	},

	selectModel: function(id) {
		model = $('#'+id);
		$('.model').addClass('dimmed');
		model.removeClass('dimmed');

		for (var r in this.models[id].relations) {
			var relatedId = this.models[id].relations[r].model;
			$('#'+relatedId).removeClass('dimmed');
			this.Relations.connectModel(model, $('#'+relatedId));
		}
	},
	
	deselectModel: function(id) {
		$('.model').removeClass('dimmed');
		this.Relations.clearCanvas();
	},

	Circle: {
		angle: 80,

		render: function() {
			var listElements = $('a.model');
			var step = (2*Math.PI) / listElements.length;
			var radius = Math.ceil(listElements.length * 16.666);
			var centerX = $('#content').width() / 2;
			var centerY = $('#content').height() / 2;
			var ovality = 1.6;
			var centerCorrectX = -75;
			var centerCorrectY = -35;

			for(var i = 0; i<listElements.length; i++) {
				var element = listElements[i];
				var left = Math.round(centerX + (radius * ovality) * Math.cos(this.angle)) + centerCorrectX;
				var top = Math.round(centerY + radius * Math.sin(this.angle)) + centerCorrectY;
				element.style.left = left+"px";
				element.style.top = top+"px";
				this.angle+=step;   
			}
		}
	}
};

$(document).ready(function(){
	Datamodel.Circle.render();
	Datamodel.Relations.initCanvas();
	$('#content').fadeIn(1000);

	$("a.model").mouseenter(function(){
		Datamodel.selectModel(this.id);
		// $("#dimmer").fadeIn();
		// $('ul.fields').hide();
		// $(this).find('ul.fields').fadeIn();
		return false;
   });

	$("a.model").mouseleave(function(){
		Datamodel.deselectModel(this.id);
		// $(this).find('ul.fields').hide();
		// $("#dimmer").fadeOut();
		return false;
   });

});