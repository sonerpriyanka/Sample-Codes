@foreach ($questions as $questionIndex=>$question)
                    @if($question->image_url)
                      <div v-show="currentQuestion == {{$questionIndex}}" class="quiz-wrap with-img clearfix" v-cloak>
                    @else
                      <div v-show="currentQuestion == {{$questionIndex}}" class="quiz-wrap" v-cloak>
                    @endif

                    <div class="question-answer-wrap">
                      <div class="question">
                        <!--<span class="question-tag">q{{$questionIndex+1}}</span>-->{!!$question->content!!}
                      </div>
                      <div class="options">
                        @foreach($question->answers as $answer)
                          @if ($question->type == "checkbox")
                          <div class="form-group">
                          <input @change="answerChange($event)" data-aval="{{$answer->value}}" id="choice-{{$question->id}}-{{$answer->id}}" name="question-{{$question->id}}" value="{{$answer->id}}" type="checkbox">
                          <label for="choice-{{$question->id}}-{{$answer->id}}" class="custom-checkbox">
                            <i class="material-icons md-18">check</i>
                          </label>
                          <label for="choice-{{$question->id}}-{{$answer->id}}" class="option">{{$answer->content}}</label>
                          @if($answer->type == "other")
                            <input name="question-{{$question->id}}" style="margin-top:10px;" class="form-control data-input" type="text" value="" />
                          @endif
                          </div>
                          @elseif ($question->type == "radio")
                          <div class="form-group">
                          <input @change="answerChange($event)" data-aval="{{$answer->value}}" id="choice-{{$question->id}}-{{$answer->id}}" name="question-{{$question->id}}" value="{{$answer->id}}" type="radio">
                          <label for="choice-{{$question->id}}-{{$answer->id}}" class="custom-radio"></label>
                          <label for="choice-{{$question->id}}-{{$answer->id}}" class="option">{{$answer->content}}</label>
                          @if($answer->type == "other")
                            <input name="question-{{$question->id}}" style="margin-top:10px;" class="form-control data-input" type="text" value="" />
                          @endif
                          </div>
                          @endif
                        @endforeach

                        @if($question->type == 'text')
                        <textarea style="height:160px;" name="textarea-{{$question->id}}" @keydown="currentAQuestion" @keyup="currentAQuestion" id="question-answers-textarea" class="input-box text_questions_type"  type="text" placeholder="Enter your answer here."></textarea>
                        <input style="display:none;" name="question-{{$question->id}}" @keydown="currentAQuestion" @keyup="currentAQuestion"  class="input-box text_questions_type"  type="hidden" placeholder="Enter your answer here." value="" />
                        @endif
                      </div>
                    </div>
                    @if($question->image_url)
                    <div class="text-center question-img-wrap">
                      <img class="img-responsive" src="{!!$question->image_url!!}" style="width:{{$question->img_saved_width}}px;" />
                    </div>
                    @endif

                  </div>
          @endforeach