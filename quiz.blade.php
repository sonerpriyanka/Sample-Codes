<!DOCTYPE HTML>
<html lang="en-US">
  <head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <meta property="og:title" content="{{$quiz->name}}" />
  <meta property="og:image" content="" />

  <!-- CSRF Token -->
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <!--"user-scalable=no" is for dissable zoomming on mobile browser -->

  <title>{{$quiz->name}}</title>
  <script>
        window.Laravel = <?php echo json_encode([
            'csrfToken' => csrf_token(),
        ]); ?>
  </script>
  <link href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,600,700,900" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css?family=Nunito:300,400,600,700" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
  <link rel="stylesheet" href="/css/style.css">
  <script src="https://use.fontawesome.com/e520ba4c5d.js"></script>
  <style>
  /********** Template Survey Styles Start*********/

  .page-title{
    text-align: center;
    font-size: 24px;
    font-weight: 500;
    color: #414968;
    margin: 7px 0 17px;
  }
  .top-btn-wrap .btn{
    min-width: 85px;
  }
  .top-btn-wrap .btn.btn-small-2{
    padding: 10px 15px !important;
  }
  .top-btn-wrap .next-btn{
    margin: 0 0 0 17px;
  }
  .btn-small-2{
    padding: 10px 15px;
  }
  .template-survey-wrap .quiz-wrap .option{
    margin-left: 4px !important;
  }
  .template-survey-wrap .quiz-wrap .options .form-group{
    margin-bottom: 12px !important;
  }
  .template-survey-wrap{
    background: none;
    margin-top: 0px;
  }
  .template-survey-wrap .quiz-wrap{
    border: none;
    background: none;
  }
  .template-survey-wrap .img-option-wrap img{
    width: 100%;
  }
  .template-survey-wrap .quiz-wrap .question-tag{
    margin-right: 18px;
  }
  .template-survey-wrap .quiz-wrap .question-tag + span{
    width: calc(100% - 40px);
  }

  .template-survey-wrap .quiz-wrap .input-box{
    border-width: 0 0 1px;
    width: 100%;
    background-color: transparent;
    border-color: #979797;
    padding: 9px 0;
    margin-top: 19px;
    color: #02bdf2;
    outline: 0;
    font-size: 16px;
    font-weight: 400;
  }
  .template-survey-wrap .quiz-wrap .input-box::-webkit-input-placeholder {
    color: rgba(2,189,242, 0.5);
  }
  .template-survey-wrap .quiz-wrap .input-box::-moz-placeholder {
    color: rgba(2,189,242, 0.5);
  }
  .template-survey-wrap .quiz-wrap .input-box:-ms-input-placeholder {
    color: rgba(2,189,242, 0.5);
  }
  .template-survey-wrap .quiz-wrap .input-box:-moz-placeholder {
    color: rgba(2,189,242, 0.5);
  }
  #overlay h3
  {
    left:37%; 
    top:18%; 
    font-size:35px; 
    text-align:center; 
    position:absolute; 
    color: #fff;
  }
  @media (max-width: 680px){
    .top-btn-wrap {
        position: relative;
        text-align: center;
        right: 0;
    }
    #overlay h3
    {
      left:20%; 
      top:18%; 
      font-size:20px; 
      text-align:center; 
      position:absolute; 
      color: #fff;
    }
    .bs-example{
    width: 100%;
    top: 10%;
    padding: 200px;
}
  }
  /********* Template Survey Styles End **********/
  .form-control{
    font-size:16px;
    height:50px;
  }
.bs-example{
    position: absolute;
    width: 100%;
    top: 10%;
    padding: 200px;
}

  #overlay {
  position: fixed;
  display: block;
  width: 100%;
  height: 100%;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0,0,0,0.6);
  z-index: 2;
  cursor: pointer;
  user-select: none;
}
</style>
  </head>
  <body>
  <div id="app">
    <section class="build-top-bar">
      <div class="container-fluid">
        <div class="row">
          <div class="col-xs-12 text-center">
            @if($quiz->banner_url)
              <img style="margin:0 auto; width: {{$quiz->img_saved_width}}px;" class="img-responsive" src="{{$quiz->banner_url}}" />
            @endif
            <h1 class="page-title">{{$quiz->name}}</h1>
          </div>
        </div>
      </div>
    </section>

    <section>
      <div class="container">
        <div class="row">
          <div class="col-xs-12 col-md-10 col-md-offset-1">
            <div class="main-content-wrap template-survey-wrap">

            <div class="clearfix"></div>
      <form id="leadForm" @submit.prevent="submitLead($event)">
        <div class="margin-bottom-20 quiz-wrap" v-if="currentQuestion == -1">
        <div v-if="'{!!$quiz->hasOptinContent()!!}' != ''" class="margin-bottom-20" style="font-weight:normal;">
        {!!$quiz->optinContent()!!}
            </div>
        <div class="row">
          <div class="col-xs-12 col-sm-8 @if($quiz->right_banner_url)@else col-sm-offset-2 @endif">
            <div class="form-group">
              <label class="control-label">First Name</label>
              <input name="name" id="user_name" v-model="name" @keydown="proceedBtnDisabled" class="form-control" type="text" placeholder="Enter your name." data-parsley-type="alphanum" data-parsley-type-message="First name only!" required autocomplete="off"/>
            </div>
            <div class="form-group">
              <label class="control-label">Your Email</label>
              <input name="email" id="user_email" v-model="email" @keydown="proceedBtnDisabled"  class="form-control" type="text" placeholder="Enter your email." data-parsley-type="email" required autocomplete="off"/>
              <span id="helpBlock" class="help-block has-error" v-if="errorExists">Invalid email. You've already been reviewed.</span>
            </div>
            <div class="text-center">
              <button type="submit" class="btn btn-primary proced-btn" disabled="disabled">Proceed ></button>
            </div>
          </div>
          @if($quiz->right_banner_url)
            <div class="col-xs-12 col-sm-4">
                <img style="margin:0 auto; width: {{$quiz->right_img_saved_width}}px;" class="img-responsive" src="{{$quiz->right_banner_url}}" />
            </div>
          @endif
        </div>
        </div>
      </form>
              <form id="quiz" @submit.prevent="submitForm($event)">
        <input type="hidden" name="quiz_id" value="{{$quiz->id}}" />
        <input type="hidden" name="hash" value="" v-model="hash" />
        <input type="hidden" name="preview" value="" v-model="isPreview" />
        {{csrf_field()}}
        <div class="box-wrap">
          <div id="overlay" style="display:none; ">
            <h3>Analyzing your results...</h3>
            <div  class="bs-example text-center">
<!--              <h3 style="left:41%; top:50%; text-align:center; position:absolute; color: #fff;">Analyzing your results...</h3>-->
              <div class="progress progress-striped active">
                <div class="progress-bar"></div>
              </div>
            </div>
          </div>
          <div class="question_html">
          </div>

       
          <div class="top-btn-wrap clearfix" v-if="currentQuestion >= 0">
            <a v-if="allowBack && currentQuestion > 0" href="#" @click.prevent="previousQuestion" class="btn btn-primary btn-small-2 pull-left proced-btn">Previous</a>
            <a v-if="currentQuestion + 1 != questionCount && currentQuestionAnswered && questionProcessed "  href="#" @click.prevent="nextQuestion" class="btn btn-primary btn-small-2 next-btn pull-right proced-btn">Next</a>
          </div>
          <div v-show="currentQuestion + 1 == questionCount && fillable"  class="quiz-wrap text-center" id="submitAnswers">
            <button type="submit" class="btn btn-primary">Submit My Answers</button>
            </div>

        </div>
              </form>


            </div>
          </div>
        </div>
      </div>
    </section>    

<footer>
  <small> Copyright {{ date('Y') }} by Kim Klaver. All rights reserved.</small>
</footer>
  </div>
<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.16.2/axios.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/vue/2.3.4/vue.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/vue-focus/2.1.0/vue-focus.min.js"></script>
  <script src="/js/parsley.min.js"></script>
  <script src="/js/serializeobject.js"></script>
  <script type="text/javascript">
		var i = 0;
		function makeProgress(){
			if(i < 100){
				i = i + 1;
				$(".progress-bar").css("width", i + "%").text(i + " %");
			}
      
			// Wait for sometime before running this script again
			setTimeout("makeProgress()", 40);
		}

    
	</script>
  
  <script>
   
    axios({
      method:'post',
      url:'{{url("readinessritual/questionshtml")}}',
      data:{
        'quiz_id':{{Request::segment(3)}}
      }
    }).then(function(response){
      $('.question_html').html(response.data);
      var app = new Vue({
        el:'#app',
        data:{
          questionProcessed:true,
          isPreview:{{$preview}},
          otherSelected:[],
          triggerchange: false,
          fillable:false,
          questions: [],
          questionLookup:[],
          qualifiers:[],
          allowBack:{{$quiz->allowBack}},
          quiz_id: {{$quiz->id}},
          quiz_name: "{{$quiz->name}}",
          currentQuestion: {{($preview == 1)?0:-1}},
          questionCount:{{sizeof($questions)}},
          buttonIsLoading: true,
          name: '',
          email: '',
          hash: '{{$hash}}',
          isLoading: true,
          errors: {},
          lead_id:false,
          affiliateId:'{{$affiliateId or 'false'}}',
          errorExists: false,
          img_percent: {{$quiz->img_percent}}
        },
      
        methods:{
          showSubmitBtn:function(){
            console.log('dfgfdg');
            if(this.questions[this.currentQuestion].response_required == 0){
                return false;
            }else{
              return true;
            }
          },
          proceedBtnDisabled:function(){
            
            if($("input[name='name']").val() !='' && $("input[name='email']").val() !=''){
              $(".proced-btn").attr('disabled',false);
            }else{
              $(".proced-btn").attr('disabled',true);
            } 
          },
          currentAQuestion:function(e){
            //$("input[name='question-"+this.questions[this.currentQuestion].id+"']").val()
          
            $("input[name='question-"+this.questions[this.currentQuestion].id+"']").val($("textarea[name='textarea-"+this.questions[this.currentQuestion].id+"']").val());
            
            this.triggerchange = !this.triggerchange;
            if(this.questions[this.currentQuestion].response_required == 0){
              if(this.questions[this.currentQuestion].type == "checkbox" || this.questions[this.currentQuestion].type == "radio"){
                
                var filled = 0;
                $("input[name='question-"+this.questions[this.currentQuestion].id+"']:checked").each(function(){
                                                              filled++;
                                                            });

                if(filled > 0){
                 this.fillable=true;
                  return true; 
                }
                
              }else if(this.questions[this.currentQuestion].type == "text"){
                var val = $("input[name='question-"+this.questions[this.currentQuestion].id+"']").val();


                if(val !=''){
                   this.fillable=true;
                   return true;
                }
              }
              this.fillable=false;
              return false;
            }else{
               this.fillable=true;
              return true;
            
            }
             
          },
          
          getPosts() {
            var app = this;
            axios({
              method:'post',
              url:'{{url("/readinessritual/questions/")}}',
              responseType: 'json',
              data:{
                'quiz_id':this.quiz_id
              }
           }).then((response) => {
                this.questions = response.data;
                this.qualifiers = response.data[0].qualifier;
              }).catch( error => { console.log(error); });
          },
          submitLead:function(event){
            if( !$("#leadForm").parsley().validate() )
              return;

            $(event.target).find('button').addClass("button-is-loading");
            var app = this;

            axios({
              method:'post',
              url:'{{url("/listener/submitLead")}}',
              responseType: 'text',
              data:{
                // 'X-CSRF-Token': Laravel.csrfToken,
                'name':this.name,
                'email':this.email,
                'quiz_id':this.quiz_id
              }
            }).then(function(response){
              $(event.target).find('button').removeClass("button-is-loading");
              app.lead_id = response.data;
              app.hash = response.data;
              ++app.currentQuestion;
              console.log("Success");
            }).catch(function(error){
              if(error.response.data=="exists"){
                app.errorExists = true;
              }
              $(event.target).find('button').removeClass("button-is-loading");
            });
          },
          nextQuestion:function(event){
            this.fillable = false;
            this.questionProcessed = false;
            var counter = 1;
            if(this.currentQuestion + 1 != this.questionCount){
              while(this.currentQuestion + counter != this.questionCount && !this.checkQualifier(this.currentQuestion + counter)){
                ++counter;
              }
            }
            
              if(this.currentQuestion + counter >= this.questionCount){
                
                var event = {};
                event.target = $("#quiz");
                this.submitForm(event);
              }else{
                this.currentQuestion += counter;
                this.questionProcessed = true;
              }
              if(this.currentQuestion + counter >= this.questionCount){
                this.currentQuestionAnswered;
              }
            
          },
          previousQuestion:function(){
            var counter = 1;
            if(this.currentQuestion - 1 >= 0){
              while(this.currentQuestion - counter >= 0 && !this.checkQualifier(this.currentQuestion - counter)){
                ++counter;
              }

              this.currentQuestion -= counter;
            }
          },
          checkQualifier:function(questionIndex){
           
            var conditionsMatched = 0;
            var app = this;
            
            if(questionIndex == this.questionCount)
              return false
          
            //  this.questions[questionIndex].qualifier.length
            for(i = 0 ; i < this.questions[questionIndex].qualifier.length ; ++i ){
             
              if(this.questions[questionIndex].qualifier[i]["condition"] == '=='){
                console.log(this.questions[questionIndex].qualifier[i]["question"]);
                console.log(this.questions[questionIndex].qualifier[i]["value"]);
               // $("#quiz").find("[name='question-"+this.questions[this.questions[questionIndex].qualifier[i]["question"]].id+"'][value='"+this.questions[questionIndex].qualifier[i]["value"]+"']")
              if($("#quiz").find("[name='question-"+this.questions[questionIndex].qualifier[i]["question"]+"'][value='"+this.questions[questionIndex].qualifier[i]["value"]+"']").is(':checked')){
                  ++conditionsMatched;
                }
              }else if(this.questions[questionIndex].qualifier[i]["condition"] == '!='){
                if(($("#quiz").find("[name='question-"+this.questions[questionIndex].qualifier[i]["question"]+"'][value='"+this.questions[questionIndex].qualifier[i]["value"]+"']").is(':checked')) ){
                   ++conditionsMatched;
                }
              }else if(this.questions[questionIndex].qualifier[i]["condition"] == '>='){
                 $("#quiz").find("[name='question-"+this.questions[questionIndex].qualifier[i]["question"]+"']").each(function(){
                  if( $(this).is(':checked') && $(this).attr("data-aval") && ($(this).attr("data-aval") >=  parseInt(app.questions[questionIndex].qualifier[i]["value"])) ){
                     ++conditionsMatched;
                  }
                 });

              }else if(this.questions[questionIndex].qualifier[i]["condition"] == '<='){
                $("#quiz").find("[name='question-"+this.questions[questionIndex].qualifier[i]["question"]+"']").each(function(){
                  if( $(this).is(':checked') && $(this).attr("data-aval") && ($(this).attr("data-aval") <=  parseInt(app.questions[questionIndex].qualifier[i]["value"])) ){
                    ++conditionsMatched;
                  }
                 });
              }

              //this.questions[questionIndex].qualifier[i]["condition"];
              //this.questions[questionIndex].qualifier[i]["value"];
              //this.questions[questionIndex].qualifier[i]["question"];
            }

            //console.log(conditionsMatched);

            if(conditionsMatched == this.questions[questionIndex].qualifier.length)
              return true;

              return false;
          },
          submitForm:function(event){
            
            console.log(event.target);
            console.log($(event.target).serialize());
            $(event.target).addClass("button-is-loading");
            axios({
              method:'post',
              url:'{{(!$preview)?url("/readinessritual/submit"):url("/readinessritual/submitpreview")}}',
              responseType: 'text',
              data:$(event.target).serializeObject()

            }).then(function(response){
              $('#overlay').css('display','block');
              makeProgress();
              setInterval(function(){ 
                window.location.href = response.data;
              }, 6000);
              
              $(event.target).find('button').removeClass("button-is-loading");

              console.log("Success");
            }).catch(function(error){
              alert(error.response.data);
              $(event.target).find('button').removeClass("button-is-loading");
            });

          },
          answerChange:function(event){
           
           this.triggerchange = !this.triggerchange;
          
           if(this.questions[this.currentQuestion].type == "radio"){
             this.currentQuestionAnswered;
           }else if(this.questions[this.currentQuestion].type == "checkbox"){
             this.currentQuestionAnswered;
           }
          },
          otherSelected:function(question_id,event){
            if($(event.target).is(":checked"))
              this.otherSelected[question_id] = true;
            else{
              if(this.otherSelected[question_id]){
                for (var key in this.otherSelected) {
                  if (key == question_id) {
                    myArray.splice(key, 1);
                  }
                }
              }
            }
          }
        },
        
        computed:{
         
          currentQuestionAnswered:function(){
            if (typeof this.questions[this.currentQuestion] === 'undefined') return;
            console.log(this.questions[this.currentQuestion]);
            console.log(this.questions[this.currentQuestion].response_required);
            this.triggerchange = !this.triggerchange;
            if(this.questions[this.currentQuestion].response_required == 0){
             
              if(this.questions[this.currentQuestion].type == "checkbox" || this.questions[this.currentQuestion].type == "radio"){
               
                var filled = 0;
                $("input[name='question-"+this.questions[this.currentQuestion].id+"']:checked").each(function(){
                                                              filled++;
                                                            });
                if(filled > 0){
                  this.fillable=true;
                  return true;
                 
                  
                }
              }else if(this.questions[this.currentQuestion].type == "text"){
                var val = $("input[name='question-"+this.questions[this.currentQuestion].id+"']").val();       
                if(val !=''){
                  this.fillable=true;
                  return true;
                }
              }
              this.fillable=false;
              return false;
            }else{
              this.fillable=true;
              return true;
            
            }
          }
        },
        created:function(){
          //Create question index
          for(var i = 0 ; i < this.questions.length ; ++i){
            this.questionLookup[this.questions[i].id] = i;
          }
          
        },
        mounted:function(){
          this.buttonIsLoading = false;
          this.isLoading = false;
          this.getPosts();
        }
      });
    });
  </script>
  </body>
</html>
